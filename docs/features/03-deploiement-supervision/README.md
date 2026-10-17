# F03 — Déploiement continu et supervision

| Lot | Priorité | Estimation |
|---|---|---|
| 0 — Socle | MVP | <!-- est -->25 tâches · 12,5 h<!-- /est --> |

## Objectif et valeur

Mettre en production, dès le lot 0, une image unique (SPA + API) sur un VPS **Hetzner Cloud** (Docker Swarm derrière Traefik), **déployée à chaque fusion sur `main`** par webhook Portainer depuis GitHub Actions (dépôt public), avec supervision (Sentry UE, sonde externe, `/health`, journaux JSON), sauvegardes automatiques Hetzner et `make backup`, et un domaine d'envoi **Brevo** authentifié et chauffé.

Valeur : chaque lot est livré en production en quelques minutes, sans coupure ; les risques d'exploitation (migrations, secrets, TLS, délivrabilité) sont traités dès la première semaine. Le domaine est **neuf** : sa chauffe commence en S44 pour que les confirmations du 13/11 arrivent en boîte de réception.

**Sans préproduction (arbitrage Q5, T52)**, chaque fusion part directement en production, y compris pendant les inscriptions ouvertes. Garde-fous : CI verte obligatoire (protection de `main`), e2e Playwright sur l'image construite **avant** tout déploiement, migrations compatibles avec la version précédente, retour arrière automatique (`failure_action: rollback`) ou manuel dans Portainer, gel automatique pendant l'édition (F27).

## Personas concernés

- **Mainteneur technique** : fusionne, voit la nouvelle version en ligne, revient en arrière en un clic, est alerté en cas de panne.
- **Tous les priants** (indirectement) : reçoivent les e-mails (liens personnels) et accèdent au service pendant les 24 heures.

## User stories

### US-03.1 — Image de production unique
En tant que **mainteneur**, je veux une image FrankenPHP qui sert la SPA et l'API sur la même origine afin que front et back soient toujours déployés ensemble.

- **Étant donné** un push sur `main`, **quand** la CI construit l'image, **alors** elle est publiée sur GHCR (`:sha`, `:main`) en moins de 5 minutes avec le cache.

### US-03.2 — Stack Swarm sécurisée
En tant que **mainteneur**, je veux une stack (Traefik, app ×2, worker, PostgreSQL, Portainer) avec TLS automatique et en-têtes de sécurité afin d'exposer un service robuste.

- **Étant donné** la stack déployée, **quand** j'interroge le site, **alors** il répond en HTTPS avec HSTS, une CSP stricte et `Referrer-Policy: same-origin`, et Portainer n'est joignable que depuis les IP autorisées.

### US-03.3 — Déploiement à chaque fusion, sans coupure
En tant que **mainteneur**, je veux qu'une fusion sur `main` déploie le worker (migrations) puis l'app, après une CI et des e2e verts, afin de livrer sans interruption.

- **Étant donné** une fusion sur `main`, **quand** l'image et les e2e sont verts, **alors** la CI appelle les webhooks, le worker joue les migrations, les nouveaux réplicas attendent un schéma à jour, et la CI réussit quand `/health` renvoie le nouveau SHA (10 min maximum).
- **Étant donné** des e2e rouges sur l'image, **quand** la CI se termine, **alors** rien n'est déployé.
- **Étant donné** un réplica qui ne démarre pas, **quand** la sonde de santé échoue, **alors** Swarm revient automatiquement à la version précédente.

### US-03.4 — Être alerté
En tant que **mainteneur d'astreinte**, je veux une sonde externe, Sentry et des journaux exploitables afin de détecter et diagnostiquer une panne.

- **Étant donné** le site indisponible, **quand** la sonde (hors VPS) échoue, **alors** je reçois un e-mail et une notification sur mon téléphone en moins de 2 minutes.
- **Étant donné** une exception, **quand** elle remonte à Sentry, **alors** elle ne contient aucune adresse e-mail.

### US-03.5 — E-mails délivrés depuis un domaine neuf
En tant qu'**organisatrice**, je veux que les e-mails arrivent en boîte de réception afin que les priants reçoivent leur lien personnel.

- **Étant donné** le domaine authentifié chez Brevo, **quand** j'envoie un e-mail de test, **alors** SPF, DKIM et DMARC sont valides, le score mail-tester est d'au moins 9/10 et le lien n'est pas réécrit par un suivi des clics.
- **Étant donné** la chauffe menée de S44 à S46, **quand** les confirmations partent le 13/11, **alors** elles arrivent en boîte de réception chez Gmail, Outlook, Orange, Free et iCloud.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : exigences §6 (disponibilité, délivrabilité SPF/DKIM/DMARC, sécurité) ; RG-NO-1 (expéditeur « Communion de prière Notre-Dame du Mont-Carmel »).
- **Décisions techniques** : T1, T7, T30, T33, T36 à T44, T47 (Brevo), T48 (Hetzner), T49 (domaine neuf chauffé), T50 (dépôt public), T51 (déploiement à chaque fusion), T52 (pas de préproduction) ; §11, §12.

## Dépendances

- F00 (Dockerfile `php-base`, CI), F02 (squelette rejoué en production).
- Actions externes de la semaine du 19/10 : achat du domaine, comptes Brevo et Hetzner, dépôt GitHub public (voir l'index).

## Hors périmètre

- Préproduction (écartée, arbitrage Q5).
- Service Mercure en production (→ F18).
- Gel des déploiements pendant l'édition, édition de test en production, runbook complet et test de restauration (→ F27).
- Haute disponibilité multi-nœuds.

## Estimation

<!-- est -->**25 tâches × 0,5 h = 12,5 h** (3 `back`, 1 `front`, 20 `infra`, 1 `doc`). Détail dans [taches.md](taches.md).<!-- /est -->
