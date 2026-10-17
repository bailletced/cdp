#syntax=docker/dockerfile:1

# Cibles : php-base → dev (T35). Les cibles front-build et prod arrivent avec F03.
FROM dunglas/frankenphp:1-php8.5 AS php-base

SHELL ["/bin/bash", "-euxo", "pipefail", "-c"]

WORKDIR /app

# tzdata : données de fuseau à jour pour l'heure de Paris (§4.6).
# hadolint ignore=DL3008
RUN <<-EOF
	apt-get update
	apt-get install -y --no-install-recommends tzdata
	install-php-extensions apcu intl opcache pdo_pgsql
	rm -rf /var/lib/apt/lists/*
EOF

ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"
COPY --link docker/frankenphp/conf.d/10-app.ini $PHP_INI_DIR/app.conf.d/

FROM php-base AS dev

ENV APP_ENV=dev
ENV XDEBUG_MODE=off
ENV COMPOSER_ALLOW_SUPERUSER=1

# git et unzip : nécessaires à Composer.
# hadolint ignore=DL3008
RUN <<-EOF
	mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
	apt-get update
	apt-get install -y --no-install-recommends git unzip
	install-php-extensions @composer xdebug zip
	rm -rf /var/lib/apt/lists/*
EOF

COPY --link docker/frankenphp/conf.d/20-app.dev.ini $PHP_INI_DIR/app.conf.d/

COPY --link --chmod=755 docker/frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
ENTRYPOINT ["docker-entrypoint"]
