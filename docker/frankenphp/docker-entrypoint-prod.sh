#!/bin/sh
set -e

# Entrypoint prod (F03-T07) : deux modes — app (serveur web) et worker (migrations + messenger).
# Usage:
#   docker-entrypoint app frankenphp run --config /etc/frankenphp/Caddyfile
#   docker-entrypoint worker

MODE="${1:-app}"
shift || true

case "$MODE" in
  app)
    # Attend que le schéma soit à jour (le worker joue les migrations).
    # 60 itérations × 5 s = 5 minutes max.
    echo "[app] Waiting for schema to be up-to-date (max 5 min)..."
    i=0
    while [ "$i" -lt 60 ]; do
      if php bin/console doctrine:migrations:up-to-date --no-interaction --quiet 2>/dev/null; then
        echo "[app] Schema is up-to-date."
        break
      fi
      i=$((i + 1))
      if [ "$i" -eq 60 ]; then
        echo "[app] Timeout: schema not up-to-date after 5 minutes." >&2
        exit 1
      fi
      sleep 5
    done

    exec docker-php-entrypoint frankenphp run --config /etc/frankenphp/Caddyfile
    ;;

  worker)
    # Joue les migrations, puis consomme les messages.
    echo "[worker] Running migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --quiet

    # Un processus par file : un worker Messenger ne peut écouter (LISTEN/NOTIFY
    # PostgreSQL) qu'une seule table à la fois, et les transports doctrine du
    # projet ont des tables séparées (async_transactional, async_bulk).
    echo "[worker] Starting messenger consume (async_transactional)..."
    php bin/console messenger:consume async_transactional --time-limit=3600 --memory-limit=128M &
    TRANSACTIONAL_PID=$!
    echo "[worker] Starting messenger consume (async_bulk)..."
    php bin/console messenger:consume async_bulk --time-limit=3600 --memory-limit=128M &
    BULK_PID=$!

    # Arrêt propre : TERM/INT est transmis aux consommateurs.
    trap 'kill "$TRANSACTIONAL_PID" "$BULK_PID" 2>/dev/null' TERM INT

    # Si un consommateur meurt, on arrête l'autre et on sort avec son code :
    # l'orchestrateur (Swarm/Portainer) redémarre le conteneur.
    while kill -0 "$TRANSACTIONAL_PID" 2>/dev/null && kill -0 "$BULK_PID" 2>/dev/null; do
      sleep 5
    done
    kill "$TRANSACTIONAL_PID" "$BULK_PID" 2>/dev/null
    wait "$TRANSACTIONAL_PID" 2>/dev/null
    STATUS=$?
    wait "$BULK_PID" 2>/dev/null
    exit "$STATUS"
    ;;

  *)
    echo "Usage: docker-entrypoint {app|worker} [command...]" >&2
    exit 1
    ;;
esac
