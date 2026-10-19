# F03 — Déploiement continu et supervision · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 3 | 1,5 h |
| `front` | 1 | 0,5 h |
| `infra` | 20 | 10 h |
| `doc` | 1 | 0,5 h |
| **Total** | **25** | **12,5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Infrastructure de base (US-03.2, US-03.5)

- [ ] **F03-T01** · `infra` VPS Hetzner Cloud amd64 (CX32 ou CPX21, ≈ 4 Go) : sauvegardes automatiques Hetzner et instantanés, pare-feu Hetzner 22/80/443, SSH par clé, mises à jour de sécurité automatiques, `docker swarm init` — *Dépend de* : — · *Fini quand* : nœud manager opérationnel, première sauvegarde Hetzner visible, accès documenté.
- [ ] **F03-T02** · `infra` Domaine neuf dédié : DNS de l'application et de `portainer.<domaine>` — *Dépend de* : F03-T01 · *Fini quand* : les deux noms résolvent vers le VPS.
- [ ] **F03-T03** · `infra` Brevo (offre gratuite, 300 e-mails/jour) : compte validé, domaine authentifié (SPF, DKIM, DMARC), suivi des clics et ouvertures désactivé pour le transactionnel, clé API → `mailer_dsn` (`brevo+api://`) — *Dépend de* : F03-T02 · *Fini quand* : score mail-tester ≥ 9/10, lien d'un e-mail de test non réécrit.

## Image de production (US-03.1)

- [x] **F03-T04** · `infra` `Dockerfile` cibles `front-build` (`node:24`, `pnpm install --frozen-lockfile`, `vite build`) et `prod` (`composer install --no-dev`, `front/dist`, utilisateur non root, opcache, argument SHA) — *Dépend de* : F00-T05, F00-T21 · *Fini quand* : l'image démarre et sert `/` et `/api`.
- [x] **F03-T05** · `infra` Caddyfile prod : `/api*`, `/auth*`, `/unsubscribe*`, `/health` → PHP (mode worker) ; statique avec repli `index.html` ; cache long des fichiers hachés — *Dépend de* : F03-T04 · *Fini quand* : une route SPA profonde rechargée renvoie `index.html`.
- [x] **F03-T06** · `back` `GET /health` : ping PostgreSQL, `version` (SHA), état du schéma + tests — *Dépend de* : F00-T19 · *Fini quand* : 200 base joignable, 503 sinon (tests).
- [x] **F03-T07** · `infra` Entrypoints `app` (attend `doctrine:migrations:up-to-date`, 5 min max) et `worker` (`migrate`, puis `messenger:consume async_transactional async_bulk scheduler_default --time-limit=3600 --memory-limit=256M`) — *Dépend de* : F03-T04, F03-T06 · *Fini quand* : un app démarré avant le worker attend le schéma (test en compose).
- [x] **F03-T08** · `infra` Configuration de prod par secrets (`%env(trim:file:…_FILE)%`) : `db_password`, `app_secret`, `mailer_dsn`, `mercure_publisher_jwt_key`, `sentry_dsn` — *Dépend de* : F03-T07 · *Fini quand* : l'image démarre sans aucune variable sensible en clair.

## Stack Swarm (US-03.2)

- [x] **F03-T09** · `infra` `docker/stack/stack.yaml` : `app` ×2 (`start-first`, healthcheck, `failure_action: rollback`), `worker` ×1 (`stop-first`), `database` (volume, réseau `internal`), réseaux `edge`/`internal`, journaux `json-file` 10m × 5 — *Dépend de* : F03-T08 · *Fini quand* : `docker stack config` valide le fichier.
- [x] **F03-T10** · `infra` Traefik v3 : provider Swarm, ACME Let's Encrypt, redirection HTTPS, routage vers `app` — *Dépend de* : F03-T09, F03-T02 · *Fini quand* : certificat valide obtenu (environnement de test Let's Encrypt, puis production).
- [x] **F03-T11** · `infra` Middleware d'en-têtes : HSTS, CSP stricte (`default-src 'self'`, Sentry autorisé), `X-Content-Type-Options`, `Referrer-Policy: same-origin`, `Permissions-Policy` — *Dépend de* : F03-T10 · *Fini quand* : en-têtes vérifiés par `curl -I` et aucune erreur CSP dans la console.
- [ ] **F03-T12** · `infra` Portainer CE + agent : interface `portainer.<domaine>` restreinte par liste d'IP, seul `/api/webhooks/*` public, webhooks des services `app` et `worker` — *Dépend de* : F03-T10 · *Fini quand* : interface refusée hors liste d'IP, webhooks enregistrés dans les secrets GitHub.
- [ ] **F03-T13** · `infra` Premier déploiement 1/2 : créer les secrets Swarm, `docker stack deploy`, vérifier les services et la base — *Dépend de* : F03-T11, F03-T12, F03-T03 · *Fini quand* : tous les services sont « running », `/health` répond 200.
- [ ] **F03-T14** · `infra` Premier déploiement 2/2 : `app:organizer:create`, squelette F02 joué à la main en production (e-mail réel reçu, session ouverte) — *Dépend de* : F03-T13, F02-T20 · *Fini quand* : lien personnel reçu et utilisé en production.

## Chaîne de livraison (US-03.1, US-03.3)

- [ ] **F03-T15** · `infra` Job CI `image` : `docker/build-push-action`, BuildKit, cache `type=gha,mode=max`, caches de montage Composer/pnpm, amd64 natif, push GHCR `:sha`/`:main` sur `main` seulement — *Dépend de* : F03-T04, F00-T30 · *Fini quand* : build < 5 min avec cache, rien n'est publié depuis une PR.
- [ ] **F03-T16** · `infra` Job CI `e2e` : Playwright sur l'image construite (compose de test avec Mailpit), bloquant pour le déploiement, traces en artefact en cas d'échec — *Dépend de* : F03-T15, F00-T27 · *Fini quand* : squelette F02 vert sur l'image de prod.
- [ ] **F03-T17** · `infra` Job CI `deploy` à chaque fusion sur `main` (après `image` et `e2e` verts) : webhook `worker` `?tag=<sha>`, puis `app`, attente de `/health` = SHA (10 min max) ; déclenchement manuel possible ; environnement GitHub `production` — *Dépend de* : F03-T14, F03-T16 · *Fini quand* : une fusion déploie sans coupure (sonde continue pendant l'opération).
- [ ] **F03-T18** · `infra` Dépôt public : protection de `main` (CI verte obligatoire), secret scanning et push protection, nettoyage GHCR (`actions/delete-package-versions`, 20 versions) — *Dépend de* : F03-T15 · *Fini quand* : fusion impossible sans CI verte, workflow de nettoyage actif.

## Supervision et sauvegarde (US-03.4)

- [ ] **F03-T19** · `back` Sentry back (`sentry/sentry-symfony`, région UE, `send_default_pii: false`, `before_send` sans e-mails, traces à 10 %) + test du filtre — *Dépend de* : F03-T08 · *Fini quand* : une exception de test arrive sans e-mail.
- [ ] **F03-T20** · `front` Sentry front (`@sentry/react`, sans Session Replay, filtrage des e-mails, release = SHA) — *Dépend de* : F03-T04 · *Fini quand* : erreur de test visible dans Sentry avec la bonne release.
- [ ] **F03-T21** · `back` Monolog JSON sur stderr + processeur qui masque les adresses e-mail + test — *Dépend de* : F00-T09 · *Fini quand* : un journal contenant un e-mail est masqué (test).
- [ ] **F03-T22** · `infra` Sonde externe hors VPS : `/health` et `/` chaque minute, alertes e-mail et notification push — *Dépend de* : F03-T14 · *Fini quand* : arrêt volontaire de l'app → alerte reçue.
- [ ] **F03-T23** · `infra` `make backup` : `pg_dump` compressé via contexte SSH, copie hors du VPS — *Dépend de* : F03-T14 · *Fini quand* : un fichier de sauvegarde daté est obtenu sur le poste du mainteneur.
- [ ] **F03-T24** · `doc` Runbook v0 `docs/exploitation/runbook.md` : déployer, revenir en arrière dans Portainer, changer un secret (`<nom>_v2`), lire les journaux, sauvegarder — *Dépend de* : F03-T17, F03-T23 · *Fini quand* : relu et suivi une fois de bout en bout.

## Délivrabilité du domaine neuf (US-03.5)

- [ ] **F03-T25** · `infra` Chauffe du domaine d'envoi (S44 à S46) : envois progressifs vers des boîtes de l'équipe (Gmail, Outlook, Orange, Free, iCloud), suivi des rapports DMARC et de la réputation Brevo — *Dépend de* : F03-T03 · *Fini quand* : aucun e-mail en indésirables sur ces 5 messageries avant le 13/11, rapports DMARC propres.
