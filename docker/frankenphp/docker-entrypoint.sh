#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	# Premier démarrage : api/vendor est vide (il n'est pas versionné).
	if [ -z "$(ls -A vendor/ 2>/dev/null)" ]; then
		composer install --prefer-dist --no-progress --no-interaction
	fi

	# Affiche la version de l'application, ou l'erreur d'initialisation.
	php bin/console -V
fi

exec docker-php-entrypoint "$@"
