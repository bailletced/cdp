#syntax=docker/dockerfile:1

# Cibles : php-base → dev (F00-T05) → front-build + prod (F03-T04).
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

# --- front-build (F03-T04) : compile la SPA React/Vite ---
FROM node:24-alpine AS front-build

ARG VITE_APP_SHA=unknown
ARG VITE_SENTRY_DSN=
ENV VITE_APP_SHA=$VITE_APP_SHA
ENV VITE_SENTRY_DSN=$VITE_SENTRY_DSN

WORKDIR /app

COPY pnpm-workspace.yaml pnpm-lock.yaml package.json ./
COPY front/package.json ./front/

# pnpm 12 est installé via npm (binaire natif) : le corepack embarqué dans
# node:24-alpine ne connaît pas la clé de signature des releases pnpm 12.
# Les postinstall à exécuter sont approuvés dans pnpm-workspace.yaml (allowBuilds).
RUN npm install --global pnpm@12.14.0 && pnpm install --frozen-lockfile --config.minimum-release-age=0

COPY front/ ./front/

RUN pnpm --filter cdp-front build

# --- prod (F03-T04) : image unique SPA + API ---
FROM php-base AS prod

ARG SHA=unknown
ENV APP_ENV=prod
ENV APP_SHA=$SHA

# hadolint ignore=DL3008
RUN <<-EOF
	mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
	apt-get update
	apt-get install -y --no-install-recommends git unzip supervisor
	install-php-extensions @composer
	rm -rf /var/lib/apt/lists/*
EOF

COPY --link docker/frankenphp/conf.d/30-app.prod.ini $PHP_INI_DIR/app.conf.d/

# Supervisor : gère les consommateurs Messenger du worker (F03-T09).
# Les redémarre auto si crash → le conteneur reste up, Swarm voit 1/1.
COPY --link docker/frankenphp/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY --link docker/frankenphp/supervisor/worker.conf /etc/supervisor/conf.d/worker.conf

WORKDIR /app/api

COPY api/composer.json api/composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-progress --no-interaction --optimize-autoloader --classmap-authoritative --ignore-platform-req=php

COPY api/ ./

# Régénère l'autoload avec le code source complet (le premier install était sans src/).
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# SPA compilée servie depuis public/ (même origine, T37).
# Le contenu de dist/ est copié dans public/ (qui existe déjà avec index.php).
COPY --from=front-build /app/front/dist/. ./public/

# Utilisateur non root.
RUN useradd -m -u 1000 -s /bin/sh appuser \
	&& chown -R appuser:appuser /app
USER appuser

COPY --link --chmod=644 docker/frankenphp/Caddyfile.prod /etc/frankenphp/Caddyfile
COPY --link --chmod=755 docker/frankenphp/docker-entrypoint-prod.sh /usr/local/bin/docker-entrypoint
ENTRYPOINT ["docker-entrypoint"]
CMD ["app"]
