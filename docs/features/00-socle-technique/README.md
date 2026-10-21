# F00 — Socle technique

| Lot | Priorité | Estimation |
|---|---|---|
| 0 — Socle | MVP | <!-- est -->31 tâches · 15,5 h<!-- /est --> |

## Objectif et valeur

Poser les fondations sur lesquelles toutes les autres features reposent : monorepo, environnement Docker reproductible, application Symfony 8 / API Platform 5 organisée selon l'architecture hexagonale d'apip-ddd, SPA React outillée, contrat OpenAPI typé et CI qualité bloquante.

La valeur est indirecte mais décisive : le **spike API Platform 5** lève le risque n° 8 des specs techniques avant d'écrire du code métier, et la CI garantit dès le premier jour la pyramide de tests et l'absence de dérive du contrat front/back.

## Personas concernés

- **Développeur** : démarre le projet en une commande, écrit du code métier sans réinventer la plomberie.
- **Mainteneur technique** : s'appuie sur une CI qui bloque toute régression.

## User stories

### US-00.1 — Trancher API Platform 5.0 ou 4.4
En tant que **développeur**, je veux un spike de portage de la glue apip-ddd vers API Platform 5 afin de choisir la version cible sur des faits et non sur des suppositions.

- **Étant donné** le commit `55a4b3b` d'apip-ddd, **quand** le prototype fait tourner un bus de commandes, un VO validé, une ressource DTO alimentée par une View et un VO embarqué, **alors** l'ADR conclut « 5.0 » ; sinon il liste les blocages et acte le repli sur 4.4.

### US-00.2 — Démarrer le projet en une commande
En tant que **développeur**, je veux `make up` pour lancer PHP, PostgreSQL, Mailpit et Vite afin de travailler sans installation locale.

- **Étant donné** un poste avec Docker, **quand** je lance `make up`, **alors** l'API répond sur `/api`, la SPA sur `/` (même origine) et Mailpit sur le port 8025.

### US-00.3 — Disposer du noyau hexagonal
En tant que **développeur**, je veux les briques Shared (Identity UUID v7, Email, bus de commandes et d'événements, `AssertValueObject`, `EmbedValueObjectsDriver`, horloge PSR-20) afin d'écrire Payload → Processor → Command → Handler et ViewFinder → Provider → Resource sans plomberie.

- **Étant donné** une commande qui lève une exception, **quand** elle passe dans `command.bus`, **alors** la transaction est annulée et aucun événement n'est publié.
- **Étant donné** un VO avec `MIN_LENGTH`/`MAX_LENGTH`, **quand** j'exporte l'OpenAPI, **alors** les bornes apparaissent dans le JSON Schema.

### US-00.4 — Contrat typé entre front et back
En tant que **développeur front**, je veux des types générés depuis l'OpenAPI et un client `openapi-fetch` afin que toute rupture de contrat soit détectée à la compilation.

- **Étant donné** un changement d'API non régénéré, **quand** la CI tourne, **alors** le job `contract` échoue.

### US-00.5 — CI qualité bloquante
En tant que **mainteneur**, je veux que chaque PR passe Rector, PHP-CS-Fixer, PHPStan max, deptrac, PHPUnit strict, Biome, `tsc` et Vitest afin de garder une base saine.

- **Étant donné** une dépendance interdite entre BC (Chain → Access), **quand** la CI tourne, **alors** deptrac fait échouer la PR.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : aucune directement (socle).
- **Décisions techniques** : T2, T3, T4, T5, T6, T8, T21, T22, T32, T34, T35 ; §4.1, §5.1, §5.3, §13, §14.

## Dépendances

- Aucune feature amont.
- Bloque toutes les autres features.

## Hors périmètre

- Image de production, stack Swarm, déploiement (→ F03).
- Thème graphique et composants accessibles (→ F01).
- Service Mercure en dev (→ F18).

## Estimation

<!-- est -->**31 tâches × 0,5 h = 15,5 h** (16 `back`, 6 `front`, 7 `infra`, 1 `test`, 1 `doc`). Détail dans [taches.md](taches.md).<!-- /est -->
