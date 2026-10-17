# Tâches courantes du projet (T35). `make help` liste les cibles.
#
# Les commandes back s'exécutent dans le conteneur `php`, les commandes front dans `node`.
# Pour les lancer ailleurs (CI), surcharger : make qa-back PHP="cd api &&"
COMPOSE ?= docker compose
PHP ?= $(COMPOSE) exec -T php
NODE ?= $(COMPOSE) exec -T node

.DEFAULT_GOAL := help

.PHONY: help up down sh migrate migration fixtures openapi qa qa-back qa-front test test-back test-front e2e

help: ## Liste les cibles
	@awk 'BEGIN {FS = ":.*## "} /^[a-z0-9-]+:.*## / {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

up: ## Démarre la stack (SPA http://localhost:8000, API http://localhost:8080, Mailpit http://localhost:8025)
	$(COMPOSE) up -d --build

down: ## Arrête la stack
	$(COMPOSE) down

sh: ## Ouvre un shell dans le conteneur PHP
	$(COMPOSE) exec php bash

migrate: ## Joue les migrations Doctrine
	$(PHP) bin/console doctrine:migrations:migrate --no-interaction

migration: ## Génère une migration Doctrine à partir du mapping
	$(PHP) bin/console doctrine:migrations:diff

fixtures: ## Charge le jeu de données de démonstration
	$(PHP) bin/console doctrine:fixtures:load --no-interaction

openapi: ## Exporte l'OpenAPI et génère les types du front
	$(PHP) bin/console api:openapi:export > front/src/api/openapi.json
	$(NODE) pnpm exec openapi-typescript src/api/openapi.json -o src/api/schema.d.ts

qa: qa-back qa-front ## Contrôles qualité back et front

qa-back: ## Rector, PHP-CS-Fixer, PHPStan, deptrac
	$(PHP) vendor/bin/rector process --dry-run
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff
	$(PHP) vendor/bin/phpstan analyse
	$(PHP) vendor/bin/deptrac analyse --config-file=deptrac_bc.yaml
	$(PHP) vendor/bin/deptrac analyse --config-file=deptrac_hexa.yaml

qa-front: ## Biome et tsc
	$(NODE) pnpm exec biome ci
	$(NODE) pnpm exec tsc --noEmit

test: test-back test-front ## Tests back et front

test-back: ## Tests PHPUnit (unit, functional, integration, acceptance)
	$(PHP) vendor/bin/phpunit $(ARGS)

test-front: ## Tests Vitest
	$(NODE) pnpm exec vitest run $(ARGS)

e2e: ## Tests Playwright sur la stack compose
	cd front && pnpm exec playwright test $(ARGS)
