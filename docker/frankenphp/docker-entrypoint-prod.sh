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
    # Joue les migrations, puis démarre supervisor qui gère les consommateurs.
    echo "[worker] Running migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --quiet

    # Supervisor démarre les 2 consommateurs Messenger (async_transactional + async_bulk)
    # et les redémarre auto s'ils plantent (autorestart=true dans worker.conf).
    # Le conteneur reste up tant que supervisord tourne → Swarm voit 1/1.
    # Inspiration : rosario/backend (supervisor + cron).
    echo "[worker] Starting supervisor (manages messenger consumers)..."
    exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
    ;;

  *)
    echo "Usage: docker-entrypoint {app|worker} [command...]" >&2
    exit 1
    ;;
esac
