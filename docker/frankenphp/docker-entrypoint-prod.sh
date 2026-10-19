#!/bin/sh
set -e

# En prod, vendor/ est déjà installé dans l'image (F03-T04).
# On affiche juste la version pour le log de démarrage.
if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	php bin/console -V || true
fi

exec docker-php-entrypoint "$@"
