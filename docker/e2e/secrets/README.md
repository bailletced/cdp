# Secrets bidon pour le test e2e sur l'image de production

Valeurs factices montées dans les conteneurs par `docker-compose.e2e.yml`
(l'image lit `APP_SECRET_FILE`, `DB_PASSWORD_FILE`, `MAILER_DSN_FILE` et
`SENTRY_DSN_FILE`, comme la stack Swarm `docker/stack/stack.yaml`).

- `app_secret` : secret de l'application (signatures, sessions).
- `db_password` : mot de passe PostgreSQL — doit correspondre au
  `POSTGRES_PASSWORD` du service `database` de `docker-compose.e2e.yml`.
- `mailer_dsn` : Mailpit de la stack e2e.
- `sentry_dsn` : vide — Sentry est désactivé pendant le test.

Ne jamais utiliser ces valeurs ailleurs qu'en CI.
