# Spécifications techniques — 24 heures de prière

**Communion de prière Notre-Dame du Mont-Carmel**

| | |
|---|---|
| Version | 0.2 — brouillon (avec les arbitrages du PO) |
| Date | 17 octobre 2026 |
| Auteur | Cédric Baillet |
| Référence | [Spécifications fonctionnelles v0.3](specs-fonctionnelles.md) · [Backlog par feature](features/README.md) |
| Statut | À valider |

Ce document décrit l'implémentation des spécifications fonctionnelles : architecture, choix techniques et exploitation. Les règles de gestion (RG-xx) renvoient aux spécifications fonctionnelles. Les décisions sont numérotées **T1 à T56** et reprises en [annexe A](#annexe-a--journal-des-décisions-techniques).

**Échéance : 1re édition du samedi 28/11/2026 20h au dimanche 29/11/2026 20h**, avec ouverture des inscriptions vers le 13/11. Claude Code implémente les tâches du [backlog](features/README.md), et Cédric relit et valide.

---

## 1. Vue d'ensemble

```
                         Internet
                            │
                ┌───────────▼────────────┐
                │  Traefik v3 (TLS, ACME)│  ← labels Swarm
                └──┬──────────┬────────┬─┘
     /, /api, /auth│          │/.well-known/mercure
          ┌────────▼───────┐  │        │ portainer.<domaine> (UI restreinte,
          │ app ×2         │  │        │  /api/webhooks public)
          │ FrankenPHP     │  │   ┌────▼──────┐
          │ SPA statique + │  │   │ portainer │
          │ API Symfony    │  │   └───────────┘
          └───┬────────┬───┘  │
   publication│        │      │
          ┌───▼─────┐  │  ┌───▼─────────┐
          │ mercure │◄─┼──┤ navigateurs │  (EventSource, signaux sans données)
          └─────────┘  │  └─────────────┘
                       │
          ┌────────────▼───┐      ┌───────────────────────────┐
          │ PostgreSQL 18  │◄─────┤ worker ×1 (même image)    │
          │ données, file, │      │ migrations, Messenger,    │
          │ sessions,      │      │ Scheduler                 │
          │ limitation     │      └─────────────┬─────────────┘
          └────────────────┘                    │ API HTTP
                                     ┌──────────▼──────────┐
                                     │ Brevo (e-mails, UE) │
                                     │                     │
                                     └─────────────────────┘

  Externes : GitHub (code, Actions, GHCR) · Sentry (UE) · sonde de disponibilité externe
```

Principes directeurs :

- **Une seule image** porte la SPA et l'API. Front et back sont donc toujours déployés ensemble, sur la même origine (T7).
- **PostgreSQL est la seule brique d'état** : données, file Messenger, sessions, jetons remember-me, compteurs anti-abus. Il n'y a pas de Redis.
- **Le temps est calculé côté back**, en UTC, avec les libellés « heure de Paris » produits par un service unique (T9).
- **La visibilité des données se décide dans le domaine**, et nulle part ailleurs (T17).

---

## 2. Socle et versions (T2)

Le projet part sur les dernières versions stables, vérifiées le 17/10/2026.

| Composant | Version | Remarque |
|---|---|---|
| PHP | 8.5 | Support actif jusqu'à fin 2027 |
| Symfony | 8.1, puis 8.2 dès sa sortie (novembre 2026) | 8.1 n'est plus maintenue après janvier 2027 ; suivre les mineures jusqu'à la LTS 8.4 (novembre 2027) |
| API Platform | 5.0 | Repli possible sur 4.4 si le portage de la glue apip-ddd bloque (voir §15, lot 0) |
| Doctrine ORM / Migrations | 3.6+ / 3.x | |
| PostgreSQL | 18 | |
| FrankenPHP | 1.13 (mode worker) | Image `dunglas/frankenphp:1-php8.5` |
| Mercure | Hub `dunglas/mercure` | Service dédié (T25, T36) |
| Node.js | 24 LTS | Build uniquement ; aucun Node en production |
| Gestionnaire de paquets JS | pnpm | Cache efficace en CI |
| React | 19 | |
| Vite | 8 | |
| TypeScript | 7.0 | Repli sur la dernière 6.x si l'outillage n'est pas compatible |
| TanStack Router / Query / Form | dernières stables | T22, T23 |
| HeroUI | dernière stable (React Aria + Tailwind CSS 4) | T23 |
| Tiptap | 3 | T27 |
| PHPUnit / PHPStan / deptrac / Rector / PHP-CS-Fixer | 13 / 2 / 4 / 2 / 3 | Alignés sur apip-ddd |
| Vitest / Playwright | 5 / 1.64 | |

---

## 3. Organisation du dépôt (T3, T33)

Le code est dans un monorepo hébergé sur **GitHub, en dépôt public** (T50). Le dépôt ne contient aucun secret : ils sont dans Swarm et dans GitHub. Les textes à méditer sont en base, pas dans le code.

```
/
├── api/                      Application Symfony (voir §4.2)
├── front/                    SPA React (voir §8.2)
├── docker/
│   ├── frankenphp/           Caddyfile, php.ini, entrypoints (app, worker)
│   └── stack/                stack.yaml (Swarm), configuration Traefik
├── Dockerfile                Multi-étapes : front-build → php-base → dev | prod
├── compose.yaml              Environnement de dev (voir §13)
├── compose.override.yaml
├── Makefile                  Tâches courantes (T35)
├── .github/workflows/        CI, build d'image, déploiement
└── docs/                     Spécifications
```

---

## 4. Architecture back-end

### 4.1 Principes (T4)

On reprend l'architecture hexagonale de [mtarld/apip-ddd](https://github.com/mtarld/apip-ddd), commit `55a4b3b` du 16/09/2026, de façon **pragmatique**.

**Repris d'apip-ddd :**

- Organisation `src/<BC>/{Domain,Application,Infrastructure}` + `src/Shared`. Les services sont déclarés par BC dans `config/services/<bc>.php`.
- Agrégats `final` (`public private(set)`, property hooks), VO `final readonly` auto-validés (webmozart/assert, bornes en constantes `MIN_LENGTH`/`MAX_LENGTH`).
- **Écriture** : Payload validé → Processor → Command `readonly` typée en VO → `CommandBusInterface` (Messenger `command.bus` avec le middleware `doctrine_transaction`) → handler `#[AsCommandHandler]`. Une commande ne renvoie rien : l'identifiant est généré par le processor.
- **Lecture** : pas de Query bus. Les providers interrogent des `*ViewFinder`, qui sont des projections DQL ou SQL et ne chargent pas les agrégats. Les DTO `#[ApiResource]` sont remplis par les providers, à partir des DTO de vue (`…View`) retournés par le finder.
- Payloads validés par `#[AssertValueObject(VO::class)]`, qui publie les bornes des VO dans le JSON Schema.
- Bus d'événements synchrone (`DispatchAfterCurrentBusStamp`), listeners `#[AsEventListener]` en Application, événements d'intégration dans `Shared/Domain/Event/Integration`.
- Horloge PSR-20 injectée, autorisée dans le domaine.

**Écarts assumés :**

| Écart | Raison |
|---|---|
| Implémentations InMemory **uniquement pour les repositories d'écriture**. Les ViewFinders n'existent qu'en Doctrine et sont testés sur PostgreSQL. | Les règles de visibilité et de temps vivent dans ces projections SQL : les doubler en mémoire coûterait cher et les testerait mal. |
| Tests d'acceptation sur une vraie base PostgreSQL | Même raison |
| Pas de MCP (`api-platform/mcp`) | Sans usage pour ce projet |
| Migrations Doctrine (absentes d'apip-ddd) | Exigées en production |
| BC **Access** allégé (dans l'esprit de `src/Subscription`) | Sous-domaine générique, fortement couplé à Symfony Security |
| `stateless: false` (API Platform) | Authentification par session (T14) |

### 4.2 Bounded contexts (T5)

Le métier est découpé en trois espaces : **Shared** (noyau partagé), **Chain** (« Chaîne », le cœur métier, en hexagonal complet) et **Access** (« Accès », identité et authentification).

deptrac impose deux règles :

- un BC ne dépend que de Shared (`deptrac_bc.yaml`) ;
- Domain → Helpers/Attributes, Application → Domain, Infrastructure → tout (`deptrac_hexa.yaml`).

```
api/src/
├── Shared/
│   ├── Domain/
│   │   ├── ValueObject/        Identity (UUID v7), Email, …
│   │   ├── Time/               ParisCalendar, SlotLabel, PartOfDay
│   │   ├── Security/           Viewer
│   │   └── Event/Integration/  PriantRegistered, PriantErased
│   ├── Application/
│   │   ├── Command/            CommandInterface, CommandBusInterface, AsCommandHandler
│   │   └── Port/               CurrentViewer, PersonalLinkIssuer, DevicePassIssuer, PriantDirectory
│   └── Infrastructure/
│       ├── Symfony/            Kernel, Messenger, Validator (AssertValueObject)
│       ├── ApiPlatform/        UriVariable, OpenApi
│       └── Doctrine/Mapping/   EmbedValueObjectsDriver
│
├── Chain/
│   ├── Domain/
│   │   ├── Programme/          Edition (+ Slot), EditionLifecycle, Text, RichText
│   │   ├── Participation/      Priant, Inscription, Intention, DisplayName, RelayMessage
│   │   ├── Communication/      Campaign, Delivery, CampaignType
│   │   ├── Policy/             VisibilityPolicy
│   │   └── Repository/         Interfaces des repositories
│   ├── Application/
│   │   ├── Command/            Commandes et handlers (voir §4.4)
│   │   ├── Listener/           Réactions aux événements
│   │   └── Port/               Notifier, CalendarFile, HtmlSanitizer, SlotPicker, ChangeSignal
│   └── Infrastructure/
│       ├── ApiPlatform/        Resource/, Payload/, State/{Provider,Processor}/
│       ├── ReadModel/          PlanningView, LiveView, MySpaceView, RelayFeedView,
│       │                       IntentionListView, DashboardView, RecipientFinder (+ interfaces *ViewFinder)
│       ├── Doctrine/           Repositories et ViewFinders Doctrine
│       ├── InMemory/           Repositories InMemory (tests)
│       ├── Mailer/             MailerNotifier, gabarits Twig
│       ├── Calendar/           EluceoCalendarFile
│       ├── Mercure/            MercureChangeSignal
│       └── Scheduler/          ChainSchedule
│
└── Access/
    ├── Domain/                 Account, Organizer, DevicePass
    ├── Application/            RequestPersonalLink, AddOrganizer, RemoveOrganizer,
    │                           listeners PriantRegistered / PriantErased
    └── Infrastructure/
        ├── Security/           SecurityUser, UserProvider, configuration login_link,
        │                       DevicePassCookie, SymfonyCurrentViewer
        ├── ApiPlatform/        Ressources /auth et /api/admin/organizers
        ├── Doctrine/
        └── Console/            app:organizer:create (amorçage du premier organisateur),
                                app:account:revoke (révocation des accès d'un compte)
```

Les sous-namespaces `Programme`, `Participation` et `Communication` de Chain préparent une extraction éventuelle en BC distincts.

**Langage du code** : le code est en anglais. Deux termes du métier sont gardés en français, faute d'équivalent anglais sans ambiguïté : `Priant` et `Inscription`.

### 4.3 Modèle de domaine

| Agrégat (BC) | Contenu | Règles portées |
|---|---|---|
| `Edition` (Chain) avec entité interne `Slot` | titre, présentation, `startsAt`, `opensAt`, `lifecycle` (`draft` \| `scheduled`), `scheduledAt`, `reminderSentAt`, `version`. `Slot` : rang, `startsAt`/`endsAt` (UTC), `TextId?` | RG-ED-1 à 6, statut calculé `statusAt($now)`, attribution des textes, « répartir dans l'ordre », duplication |
| `Text` (Chain) | titre, source, corps (`RichText`), pistes de méditation | RG-TX-3 |
| `Priant` (Chain) | `Email?` (absent si inscrit par un organisateur), `DisplayName` (un seul nom, modifiable), `consentAt?`, `unsubscribedAt?`, `erasedAt?` | RG-IN-2, RG-IN-5, §5.10 |
| `Inscription` (Chain) | `EditionId`, `SlotId`, `PriantId`, origine (`self` \| `organizer`), `prayedAt?`, `RelayMessage?`, `version`. Compte pour 1. | RG-IN-3, RG-EP-1, RG-LV-1 à 4 |
| `Intention` (Chain) | `EditionId`, `PriantId`, texte (500 caractères au plus), `anonymous`, `createdAt`. Signée du nom actuel du priant ou « Anonyme ». | RG-LV-5 à 7 |
| `Campaign` + `Delivery` (Chain) | type (`empty_slots` \| `free_message` \| `invitation` \| `eve_reminder`), objet, corps, auteur, destinataires figés, statut par destinataire | §5.9-6, idempotence des envois |
| `Account` (Access) | e-mail normalisé, `credentialsVersion` (révocation des liens), `PriantId?` | Identité de connexion |
| `Organizer` (Access) | `AccountId`, ajouté par, ajouté le | « au moins un organisateur » |
| `DevicePass` (Access) | hash du jeton, `InscriptionId[]`, `expiresAt`, `lastSeenAt` | RG-IN-6 |

Les agrégats se référencent par identité. Le verrou optimiste (`#[ORM\Version]`) est posé sur `Edition` et `Inscription`.

### 4.4 Cas d'usage (commandes)

| Domaine | Commandes |
|---|---|
| Éditions | `CreateEdition`, `UpdateEdition`, `ScheduleEdition`, `UnscheduleEdition`, `DuplicateEdition`, `AssignText`, `DistributeTexts` |
| Textes | `CreateText`, `UpdateText`, `DeleteText` |
| Participation | `RegisterInscriptions` (plusieurs créneaux), `RenamePriant`, `CancelInscription`, `MarkPrayed`, `UnmarkPrayed`, `WriteRelayMessage`, `DeleteRelayMessage`, `PostIntention`, `DeleteIntention`, `ErasePriant`, `UnsubscribeInvitations` |
| Organisateurs | `RegisterInscriptionsForSomeone` (crée un priant sans e-mail si besoin), `RenamePriant`, `MoveInscription`, `SendCampaign`, `DeliverCampaignMessage` (asynchrone), `SendEveReminders` (planifiée) ; `DeleteTestEdition` (exploitation, §12.7) |
| Access | `RequestPersonalLink`, `AddOrganizer`, `RemoveOrganizer` |

**Exemple : flux d'une inscription**

1. `POST /api/inscriptions` → `RegisterInscriptionsPayload` (créneaux, nom affiché, e-mail, consentement, champ piège).
2. `RegisterInscriptionsProcessor` : contrôle anti-abus (§6.7), génère les `InscriptionId`, puis dispatche `RegisterInscriptions`.
3. Le handler, dans une transaction :
   - retrouve le `Priant` par son e-mail, ou le crée. **S'il existe déjà, son nom n'est jamais écrasé** par le nom saisi (RG-IN-2) : seul `RenamePriant`, en session, peut le changer. Le consentement est daté une seule fois (RG-IN-5) ;
   - vérifie le statut de l'édition et que les créneaux n'ont pas commencé, sauf le créneau en cours (§5.8) ;
   - crée les `Inscription` ;
   - enregistre `InscriptionsRegistered` (et `PriantRegistered` si le priant est nouveau).
4. Les listeners, après validation de la transaction :
   - e-mail de confirmation (asynchrone) ;
   - signal Mercure ;
   - Access associe un `Account` au priant.
5. Le processor appelle `DevicePassIssuer::grant($inscriptionIds)`, qui pose le cookie de pass appareil.
6. La réponse contient les inscriptions créées et une suggestion de créneau vide (`SlotPicker`).

L'unicité (créneau, priant) est garantie par un index unique partiel. Une violation est traduite en **409** ; une violation de validation en **422**.

### 4.5 Persistance (T6, T10)

- **PostgreSQL 18**. Mapping par **attributs Doctrine dans le domaine**, VO embarqués automatiquement par `EmbedValueObjectsDriver`, comme dans apip-ddd.
- **Identifiants UUID v7** : il suffit de remplacer `Uuid::v4()` par `Uuid::v7()` dans `Identity`.
- **Créneaux en table** : `Slot` est une entité interne d'`Edition` (`edition_id`, `rank`, `starts_at`, `ends_at`, `text_id`). Les 48 créneaux sont générés à la création de l'édition et régénérés si `startsAt` change, ce qui n'est possible qu'avant la première inscription (RG-ED-3).
- **Contraintes en base** :
  - `UNIQUE (slot_id, priant_id)` (RG-IN-3) ;
  - `UNIQUE (lower(email)) WHERE email IS NOT NULL` sur `priant` (RG-IN-2) ;
  - clés étrangères vers les créneaux et les priants.
- **Index** : `inscription(slot_id)`, `inscription(priant_id)`, `slot(edition_id, rank)`.
- **Migrations** : Doctrine Migrations, versionnées dans `api/migrations/`. Elles doivent rester compatibles avec la version précédente du code (on ajoute d'abord, on retire au déploiement suivant), car les anciens réplicas tournent encore pendant la migration (§12.3).

### 4.6 Temps et fuseau horaire (T9)

- **En base** : instants en UTC (`timestamptz`), `date.timezone = UTC`.
- **`ParisCalendar`** (Shared/Domain/Time) est l'unique source de vérité. Il :
  - découpe une édition en 48 créneaux réels de 30 minutes à partir de `startsAt` ;
  - produit le libellé de chaque créneau en heure de Paris (« 3h00–3h30 »), en précisant les heures ambiguës (« 2h00 (heure d'hiver) ») ;
  - détermine le moment de la journée (soirée 18h–0h, nuit 0h–6h, matin 6h–12h, après-midi 12h–18h) ;
  - calcule les échéances, comme « la veille du début à 18h » ;
  - convertit l'heure de début saisie en heure de Paris (`resolveLocalStart`), avec **résolution automatique** (RG-ED-7) : une heure ambiguë prend sa première occurrence, une heure inexistante est décalée d'une heure. L'heure doit tomber sur :00 ou :30.
- **L'API renvoie** l'instant ISO 8601 UTC, le libellé et le moment de la journée. Le front n'ajoute que l'heure locale du navigateur, via `Intl`, si son fuseau n'est pas `Europe/Paris` (RG-PL-3).
- **Horloge** : `ClockInterface` (PSR-20) est injectée partout. Les tests utilisent `MockClock`.
- **Tests obligatoires** :
  - 25/10/2026 : passage à l'heure d'hiver, « 2h00 » existe deux fois ;
  - 28/03/2027 : dimanche de Pâques et passage à l'heure d'été, le créneau de 1h30 se termine à 3h00 ;
  - une édition « normale ».
- L'image Docker embarque des données de fuseau à jour (`tzdata` et ICU de PHP).

### 4.7 Statut d'édition (T11, T12)

Le statut est **hybride** : seules les étapes manuelles sont stockées (`lifecycle` vaut `draft` ou `scheduled`), les autres sont calculées à partir des dates.

```
statusAt(now) =
  draft                      si lifecycle = draft
  scheduled                  si lifecycle = scheduled et now < opensAt
  open                       si lifecycle = scheduled et opensAt ≤ now < startsAt
  running                    si lifecycle = scheduled et startsAt ≤ now < startsAt + 24h
  finished                   si lifecycle = scheduled et now ≥ startsAt + 24h
```

- **Requêtes SQL** : elles filtrent sur les dates. Par exemple, l'édition publique (RG-ED-1) est `lifecycle = 'scheduled' AND opens_at <= now() ORDER BY starts_at DESC LIMIT 1`.
- **Aucune tâche de fond** n'est nécessaire, et le statut reste juste même si le worker est arrêté.
- **Réagir à une échéance** (par exemple le rappel de la veille) passe par une tâche du Scheduler qui ne s'exécute qu'une fois et rattrape un éventuel retard (§7.4).

### 4.8 Événements

| Événement | Type | Consommateurs |
|---|---|---|
| `InscriptionsRegistered`, `InscriptionCancelled`, `InscriptionMoved`, `InscriptionUpdated` | Domaine (Chain) | E-mail, signal Mercure |
| `PrayedMarked`, `RelayMessageWritten`, `IntentionPosted`, `IntentionDeleted` | Domaine (Chain) | Signal Mercure |
| `PriantRegistered` | Intégration (Shared) | Access : crée ou associe l'`Account` |
| `PriantErased` | Intégration (Shared) | Access : incrémente `credentialsVersion`, supprime les passes, les jetons remember-me et les sessions |

---

## 5. API

### 5.1 Conventions (T8)

- **Format** : JSON-LD/Hydra (`member`, `totalItems`, IRI) par défaut, `application/merge-patch+json` pour PATCH.
- **Préfixes** :
  - `/api/*` : API publique et priants ;
  - `/api/admin/*` : organisateurs, seules ressources exposant des e-mails ;
  - `/auth/*` : authentification.
- **Codes HTTP** : 404 pour une ressource absente ou invisible (`MissingModelException`), 403 pour une action interdite, 409 pour un conflit (unicité, version), 422 pour une erreur de validation (`violations[]` avec `propertyPath`), 429 au-delà des limites d'envoi.
- **Cache** : `Cache-Control: private, no-store` sur toutes les ressources dont le contenu dépend de l'utilisateur (soit presque toutes).
- **Heure serveur** : en-tête `X-Server-Time` sur chaque réponse, pour corriger la dérive de l'horloge du client dans l'affichage en direct.

### 5.2 Catalogue des ressources

**Public et priants**

| Méthode et route | Accès | Rôle |
|---|---|---|
| `GET /api/editions/current` | tous | Édition publique : titre, présentation, dates, statut, compteurs |
| `GET /api/editions/{id}/slots` | tous (champs selon le profil) | Grille : libellés, état, nombre de priants, titre et source du texte ; noms et pointages pour les inscrits ; marque `mine` |
| `GET /api/editions/{id}/slot-suggestion?near={slotId}` | tous | Tirage d'un créneau vide (`SlotPicker`) : adjacent à `near` en priorité, sinon au hasard |
| `GET /api/editions/{id}/live` | tous (champs selon le profil) | Bloc « En ce moment » ou bilan |
| `GET /api/slots/{id}/text` | inscrits sur ce créneau, organisateurs | Texte complet et pistes de méditation |
| `POST /api/inscriptions` | tous (protégé contre les abus) | Inscription sur un ou plusieurs créneaux, pose du pass appareil |
| `DELETE /api/inscriptions/{id}` | priant vérifié propriétaire, avant le début du créneau | Désinscription |
| `PUT` / `DELETE /api/inscriptions/{id}/prayed` | priant vérifié propriétaire, dans la fenêtre RG-LV-1 | Pointage |
| `PUT` / `DELETE /api/inscriptions/{id}/relay-message` | priant vérifié propriétaire, dans la fenêtre RG-LV-3 | Mot de relais |
| `GET /api/editions/{id}/relay-messages` | inscrits | Fil du relais |
| `GET /api/editions/{id}/intentions` | inscrits | Intentions |
| `POST /api/intentions` · `DELETE /api/intentions/{id}` | priant vérifié inscrit · auteur ou organisateur | Dépôt et suppression d'une intention |
| `GET /api/me` · `PATCH /api/me` | priant vérifié | Profil (nom affiché, statut des invitations) · changement du nom affiché |
| `GET /api/me/inscriptions?edition=` | priant vérifié | Mes créneaux et l'historique |
| `GET /api/me/calendar.ics` | priant vérifié | Fichier agenda unique de l'édition en cours (tous ses créneaux) |
| `POST /api/me/erasure` | priant vérifié | Suppression de ses données (RG-EP-3) |
| `POST /api/me/invitations-unsubscribe` · `POST /unsubscribe/{token}` | priant vérifié · lien en un clic (RFC 8058) | Désabonnement des invitations |
| `POST /auth/link-request` | tous (protégé contre les abus) | « Recevoir mon lien » : toujours 202, quelle que soit l'adresse |
| `GET` / `POST /auth/connexion` | porteur du lien | Vérification du lien personnel (§6.2) |
| `POST /auth/logout` | session | Déconnexion |

**Organisateurs (`/api/admin`)**

| Ressource | Opérations |
|---|---|
| `editions` | Lister, créer, modifier, `POST …/schedule`, `POST …/unschedule`, `POST …/duplicate`, `GET …/dashboard` |
| `texts` | CRUD sur la bibliothèque |
| `slots` | `PUT …/{id}/text`, `POST /api/admin/editions/{id}/distribute-texts` |
| `inscriptions` | Lister par édition et par créneau (avec e-mails), créer pour quelqu'un, supprimer, `POST …/{id}/move` ; **`GET /api/admin/editions/{id}/inscriptions.csv`** (export : nom, e-mail, créneaux en heure de Paris) |
| `priants` | `PATCH …/{id}` (nom affiché) |
| `relay-messages`, `intentions` | Lister, supprimer (modération) |
| `campaigns` | `POST …/preview` (aperçu et nombre de destinataires), `POST` (envoi), `GET` (historique) |
| `organizers` | Lister, ajouter, retirer |

### 5.3 Contrat avec le front (T21)

1. `bin/console api:openapi:export --output=front/src/api/openapi.json`
2. `openapi-typescript` génère `front/src/api/schema.d.ts`.
3. Le client est `openapi-fetch`, avec `openapi-react-query` pour l'intégrer à TanStack Query.
4. La CI régénère ces fichiers et échoue s'ils diffèrent de ceux du dépôt (contrôle de dérive).
5. Les bornes des VO, publiées dans le JSON Schema par `AssertValueObject`, se retrouvent dans les types et servent à la validation côté client.

---

## 6. Sécurité

### 6.1 Niveaux d'identification et `Viewer` (T13 à T17)

| Niveau | Comment on l'obtient | Ce qu'il permet |
|---|---|---|
| Visiteur | — | Données publiques |
| Porteur d'un pass appareil | Inscription faite depuis cet appareil (cookie `cdp_pass`) | **Vue inscrit en lecture** (noms, pointages, relais, intentions) et textes de **ses** créneaux. Aucune action au nom du priant. |
| Priant vérifié | Lien personnel ou lien d'invitation → session + remember-me | Tout ce qui concerne ses inscriptions : actions, historique, effacement |
| Organisateur | Lien personnel d'un compte organisateur | Back-office |

À chaque requête, `SymfonyCurrentViewer` (Access) construit un VO `Viewer` (Shared) à partir du jeton de sécurité et du cookie de pass. Le `Viewer` contient : `priantId?`, `verified`, `coveredInscriptionIds[]` et `isOrganizer`. Un organisateur peut aussi être priant.

### 6.2 Lien personnel (T13, T16)

On utilise le **`login_link` de Symfony** :

- `signature_properties: [id, credentialsVersion]` : incrémenter `credentialsVersion` invalide tous les liens émis ;
- durée de vie fixée lien par lien (`createLoginLink($user, null, $lifetime)`) : jusqu'à la fin de l'édition concernée + 7 jours pour un lien envoyé dans le cadre d'une édition, 7 jours sinon (« Recevoir mon lien » entre deux éditions, organisateurs) ;
- destination après connexion prise dans une **liste blanche** (`/`, `/mon-espace`) et incluse dans les propriétés signées. Le lien d'invitation mène à `/`, les autres à `/mon-espace`. Il n'y a aucune redirection libre ;
- pas de `max_uses`, pour que les messageries qui préchargent les liens ne les consomment pas ;
- identifiant = UUID de l'`Account` : aucun e-mail dans l'URL ;
- `check_post_only: true`. Le lien pointe sur `GET /auth/connexion?user=…&expires=…&hash=…`, qui redirige vers la page SPA `/connexion`. Celle-ci envoie automatiquement un POST vers `/auth/connexion`, qui ouvre la session puis renvoie vers `/mon-espace`.

Les liens d'invitation sont des liens personnels (T16).

La clé `APP_SECRET` signe les liens : en changer invalide tous les liens en circulation (§12.4).

### 6.3 Session durable (T14)

- **Firewall avec état.** Session PHP stockée dans PostgreSQL (`PdoSessionHandler`), partagée entre les 2 réplicas.
- **`remember_me`** : `always_remember_me: true`, durée d'un an, `token_provider: doctrine` (jetons en base, donc révocables).
- **Cookies** : `HttpOnly`, `Secure`, `SameSite=Lax`, préfixe `__Host-` quand c'est possible.
- **Révocation** : sur `PriantErased`, ou sur demande d'un organisateur, on supprime les jetons remember-me et les sessions du compte, et on incrémente `credentialsVersion`.

### 6.4 Pass appareil (T15)

- Jeton aléatoire de 256 bits dans le cookie `cdp_pass` (`HttpOnly`, `Secure`, `SameSite=Lax`). Seul le hash SHA-256 est stocké dans `DevicePass`.
- Le pass couvre **uniquement** les inscriptions créées depuis cet appareil. Chaque nouvelle inscription faite depuis l'appareil s'y ajoute.
- Il expire à la fin de l'édition + 7 jours, et il est purgé par la tâche de nettoyage (§7.4).
- Les droits ne sont pas stockés dans le pass : ils sont recalculés à chaque requête à partir des inscriptions couvertes. Une inscription annulée ne donne donc plus rien.

### 6.5 Autorisation et visibilité (T17)

- **`VisibilityPolicy`** (Chain/Domain/Policy) applique la matrice du §4 des spécifications fonctionnelles, ainsi que RG-PL-4, RG-TX-1 et RG-LV-2. Par exemple :
  - `canSeeParticipantNames(Viewer, Edition)` ;
  - `canReadText(Viewer, Slot)` ;
  - `canActOn(Viewer, Inscription)` ;
  - `canSeeIntentions(Viewer, Edition)`.
- **Providers** : ils récupèrent les vues des ViewFinders, puis la politique met à `null` les champs non autorisés. Un champ masqué est toujours `null`, jamais absent, pour garder des types stables.
- **Voters** : les actions sont protégées par `security: "is_granted('INSCRIPTION_ACT', object)"`, et les voters délèguent à la politique.
- **Séparation stricte** : seules les ressources `/api/admin/*` contiennent des e-mails, et le firewall n'y laisse passer que le rôle organisateur.
- **Tests** : la matrice est rejouée en tests d'acceptation pour chaque profil (§10).

### 6.6 CSRF et en-têtes

- `SameSite=Lax`, plus une vérification d'origine (`Origin` et `Sec-Fetch-Site`) sur toutes les requêtes non sûres, avec la protection CSRF same-origin de Symfony.
- Pas de CORS : tout est servi sur la même origine.
- **En-têtes posés par Traefik** :
  - `Strict-Transport-Security` ;
  - `Content-Security-Policy` stricte : `default-src 'self'`, `connect-src 'self'` (Mercure est sur la même origine), Sentry autorisé ;
  - `X-Content-Type-Options` ;
  - `Referrer-Policy: same-origin` (évite de divulguer le lien personnel) ;
  - `Permissions-Policy`.

### 6.7 Anti-abus (T18)

- **Champ piège** (`website`) sur l'inscription et sur « Recevoir mon lien ». S'il est rempli, la réponse est identique à un succès, mais rien n'est fait.
- **Limitation d'envoi** (`symfony/rate-limiter`, compteurs dans PostgreSQL via un pool de cache DBAL, partagés entre réplicas) :

| Action | Par IP | Par e-mail |
|---|---|---|
| Inscription | 10 par heure | 5 par heure |
| Demande de lien | 10 par heure | 3 par heure |

- **Pas de fuite d'information** : la réponse est la même que l'adresse soit connue ou non, et l'e-mail part de façon asynchrone, sans écart de temps de réponse.
- **ALTCHA** (défi auto-hébergé, sans tiers) est intégré mais désactivé, et s'active par variable d'environnement en cas d'attaque.

---

## 7. Traitements asynchrones, e-mails, planification

### 7.1 Messenger (T29)

| Bus ou transport | Rôle |
|---|---|
| `command.bus` (synchrone) | Commandes métier, avec le middleware `doctrine_transaction` |
| `event.bus` (synchrone) | Événements, avec `DispatchAfterCurrentBusStamp` |
| Transport `async_transactional` | Doctrine sur PostgreSQL, `use_notify: true` (LISTEN/NOTIFY) : confirmation, lien personnel, rappel de la veille. **Consommé en priorité.** |
| Transport `async_bulk` | Doctrine sur PostgreSQL : `DeliverCampaignMessage` (relances, messages libres, invitations), soumis au quota journalier (§7.3) |
| Transport `failed` | Doctrine. Surveillé (§12.5), rejeu par `messenger:failed:retry` |
| `scheduler_default` | Symfony Scheduler (§7.4) |

Le worker consomme `messenger:consume async_transactional async_bulk scheduler_default`, dans cet ordre de priorité. Politique de nouvelle tentative : 5 essais, délai exponentiel (1 min, puis multiplié par 3).

### 7.2 E-mails (T30)

- Port **`Notifier`** (Chain/Application), avec l'adaptateur **`MailerNotifier`** : Symfony Mailer, `TemplatedEmail`, gabarits Twig dans `api/templates/emails/` (inky et CSS mis en ligne), mise en page sobre aux couleurs de la Communion.
- **Brevo** (T47), via le bridge API de Symfony (`symfony/brevo-mailer`), offre gratuite à 300 e-mails par jour, suffisante pour 50 à 150 priants. **Mailpit** en dev et en e2e.
- **Domaine d'envoi neuf** (T49), sans réputation. DNS configurés dès l'achat (SPF, DKIM et DMARC fournis par Brevo). Les volumes montent progressivement : d'abord les e-mails de test et de l'équipe, puis les confirmations, avant toute relance groupée.
- **Suivi des clics et des ouvertures désactivé**, sinon les liens personnels seraient réécrits.
- Domaine d'envoi authentifié (SPF, DKIM, DMARC), et nom d'expéditeur = nom de la Communion.

| E-mail | Pièces jointes et en-têtes |
|---|---|
| Confirmation d'inscription | Textes complets, lien personnel, `.ics` en pièce jointe |
| Rappel de la veille | Récapitulatif des créneaux, textes, lien personnel |
| Lien personnel | — |
| Relance « créneaux vides », message libre | Lien personnel |
| Invitation | Lien personnel, `List-Unsubscribe` et `List-Unsubscribe-Post` (désabonnement en un clic, RFC 8058) |
| Mise à jour après un déplacement par un organisateur | `.ics` mis à jour (même UID, `SEQUENCE` incrémentée) |

### 7.3 Envois de masse (campagnes)

1. `SendCampaign` crée la `Campaign` et **fige les destinataires** : une `Delivery` par destinataire, au statut `pending`, calculée par `RecipientFinder` selon le type.
2. Un message `DeliverCampaignMessage(deliveryId)` par destinataire part sur `async_bulk`.
3. Le handler n'envoie que si la `Delivery` est `pending`, puis la passe à `sent`. Un message rejoué n'envoie donc jamais deux fois.
4. **Quota journalier** (RG-NO-5) : un `DailyEmailQuota` compte les e-mails envoyés dans la journée, en heure de Paris, toutes catégories confondues. La limite est configurable (300 par défaut), dont une réserve pour le transactionnel (60 par défaut). Si le quota disponible pour les envois groupés est épuisé, le message est reporté au lendemain 8h (heure de Paris) avec un `DelayStamp`, et la `Delivery` passe à l'état `deferred`.
5. Le débit vers le fournisseur est aussi limité (`RateLimiter`, par exemple 5 par seconde).
6. Le back-office affiche l'avancement : envoyés, reportés, en attente, en échec. L'aperçu d'un envoi indique s'il sera étalé sur plusieurs jours.

Le rappel de la veille réutilise ce mécanisme, avec le type `eve_reminder`.

### 7.4 Tâches planifiées (T31)

Les tâches tournent dans **Symfony Scheduler**, consommé par le worker (1 réplica). Le planning garde son état et pose un verrou (`stateful`, `lock`) : une tâche ne s'exécute jamais deux fois.

| Tâche | Fréquence | Effet |
|---|---|---|
| `SendEveReminders` | Toutes les 5 min | Pour chaque édition dont l'échéance (veille du début à 18h, heure de Paris) est passée, qui n'a pas commencé et dont `reminderSentAt` est vide : lance la campagne `eve_reminder` et renseigne `reminderSentAt`. Si le worker était arrêté, la tâche rattrape le retard au redémarrage. |
| `PurgeExpiredCredentials` | Quotidienne | Supprime les passes appareil expirés, les jetons remember-me expirés, les sessions mortes et les anciens compteurs de limitation |
| `CheckFailedMessages` | Toutes les 15 min | Alerte (Sentry et e-mail technique) si le transport `failed` n'est pas vide |

Il n'y a **pas de purge RGPD** (T20).

---

## 8. Front-end

### 8.1 Stack (T21 à T24, T27)

| Besoin | Choix |
|---|---|
| Build | Vite 8, TypeScript, pnpm |
| Routage | **TanStack Router** : routes définies par fichiers via le plugin Vite, paramètres d'URL typés |
| Données | **TanStack Query** et `openapi-fetch` / `openapi-react-query`, sans store global |
| Formulaires | **TanStack Form**, avec validation Standard Schema (Zod ou Valibot) alimentée par les bornes du schéma OpenAPI |
| Interface | **HeroUI** (React Aria + Tailwind CSS 4), thème « carmélitain » |
| Éditeur | **Tiptap 3**, limité aux extensions Document, Paragraph, Text, Bold, Italic, Blockquote, HardBreak et History |
| Temps réel | `EventSource` vers Mercure, qui déclenche `invalidateQueries` |
| Back-office | Même SPA, section `/organisation` chargée seulement quand on y va (T24) |

### 8.2 Structure

```
front/src/
├── main.tsx, router.tsx, queryClient.ts
├── routes/                    Routes TanStack Router (fichiers)
├── features/
│   ├── planning/              Grille, bandeau des vides, bouton « Prendre un créneau vide »
│   ├── inscription/           Formulaire, confirmation, suggestion
│   ├── espace/                Mes créneaux, pointage, mot de relais, historique, mes données
│   ├── live/                  « En ce moment », progression, bilan
│   ├── relais/ intentions/
│   ├── textes/                Lecture et impression
│   └── organisation/          Back-office (chargé à la demande)
├── api/                       openapi.json, schema.d.ts (générés), client.ts, mercure.ts, errors.ts
├── ui/                        Thème HeroUI, composants partagés
└── lib/                       Heure locale (Intl), horloge corrigée, impression
```

### 8.3 Routes

| Route | Écran |
|---|---|
| `/` | Accueil et planning, « En ce moment » pendant l'édition, bilan après |
| `/inscription?creneaux=…` | Formulaire d'inscription |
| `/inscription/confirmation` | Confirmation et suggestion |
| `/connexion` | Confirmation du lien personnel (POST automatique) |
| `/recevoir-mon-lien` | Demande de lien |
| `/mon-espace` | Espace personnel |
| `/textes/$slotId` · `/textes/$slotId/imprimer` | Texte, version imprimable |
| `/relais` · `/intentions` | Fil du relais, intentions |
| `/confidentialite` | Confidentialité et mentions légales |
| `/organisation/...` | Tableau de bord, éditions, textes, inscriptions, modération, envois, organisateurs |

### 8.4 Données, erreurs, temps

- **Clés de requête** : TanStack Query les organise par ressource et par édition.
- **Erreurs** : les 422 (`violations[]`) sont converties en erreurs de champ pour TanStack Form par un utilitaire commun (`api/errors.ts`). Les 409 affichent un message métier, par exemple « Vous êtes déjà inscrit sur ce créneau ».
- **Horloge corrigée** : le décalage avec le serveur est estimé à partir de `X-Server-Time`. Le créneau en cours et la progression sont calculés côté client avec cette horloge corrigée.
- **Heure locale** : `Intl.DateTimeFormat().resolvedOptions().timeZone !== 'Europe/Paris'` déclenche l'affichage de l'heure locale en complément.

### 8.5 Thème et accessibilité

- Thème HeroUI : couleurs brun et crème, beaucoup de blanc, police lisible.
- **Corps de texte ≥ 18 px**, zones tactiles ≥ 44 px, contrastes AA vérifiés sur chaque couleur du thème. Les réglages par défaut de HeroUI sont plus compacts et doivent être revus.
- L'état d'un créneau est toujours porté par un libellé ou une icône, en plus de la couleur (« À pourvoir »).
- **Contrôles** : axe-core dans Playwright (bloquant), plus une vérification manuelle VoiceOver et NVDA avant la première édition.

---

## 9. Temps réel (T25, T26)

- **Hub dédié** : le service `mercure`, à un seul réplica (image `dunglas/mercure`). Le hub intégré à FrankenPHP n'est pas partagé entre les 2 réplicas de l'app, et la version libre de Mercure ne fonctionne pas en grappe.
- **Signaux sans données** : un seul topic public par édition, `https://<domaine>/editions/{id}`. Les messages ne contiennent que des identifiants et des types :

  ```json
  { "type": "slots", "slotIds": ["…"] }
  { "type": "relay" } · { "type": "intentions" } · { "type": "edition" }
  ```

- **Abonnés anonymes autorisés** : aucune donnée personnelle ne transite, et c'est l'API qui applique la visibilité quand le front recharge.
- **Publication** : port `ChangeSignal` (Chain/Application), avec l'adaptateur `MercureChangeSignal` (`symfony/mercure-bundle`, JWT de publication en secret Swarm). Les listeners publient sur les événements de domaine, après la validation de la transaction.
- **Mode dégradé** : si l'`EventSource` échoue, le front repasse en rafraîchissement périodique (`refetchInterval` de 60 s) et en rafraîchissement au retour sur l'onglet. Une panne du hub ne bloque donc jamais l'usage.

---

## 10. Contenus et documents

### 10.1 Texte riche (T27)

- Saisie avec **Tiptap**, limitée aux extensions du §8.1. Le HTML produit est envoyé à l'API.
- Le VO **`RichText`** passe le HTML dans le **HtmlSanitizer** de Symfony (port `HtmlSanitizer`). Seuls `p`, `br`, `strong`, `em` et `blockquote` sont autorisés, sans aucun attribut. Le contenu est rejeté en 422 s'il est vide une fois nettoyé.
- Le même HTML est rendu dans Twig (e-mails) et dans la SPA (`dangerouslySetInnerHTML` sur du contenu déjà nettoyé côté serveur).

### 10.2 Fichier agenda (.ics) (T28)

- Bibliothèque **eluceo/ical 2**, derrière le port `CalendarFile`.
- Un `VEVENT` par inscription :
  - `UID` : `inscription-{uuid}@<domaine>` ;
  - `SEQUENCE` : version de l'inscription ;
  - `DTSTART` et `DTEND` en UTC ;
  - `SUMMARY` : « 24 heures de prière — 3h00–3h30 » ;
  - `DESCRIPTION` : titre du texte et lien vers l'espace personnel ;
  - `VALARM` 10 minutes avant le début (`-PT10M`).
- Le fichier est joint à l'e-mail de confirmation et téléchargeable via `GET /api/me/calendar.ics`.

### 10.3 Version imprimable (T28)

Route SPA `/textes/$slotId/imprimer` :

- feuille de style `@media print`, `@page { size: A4; margin: 2cm }`, gros caractères ;
- contenu : horaire du créneau, nom de la Communion, texte, pistes ;
- un bouton lance `window.print()`, qui permet d'imprimer ou d'enregistrer en PDF.

Aucun service PDF côté serveur.

---

## 11. Données personnelles (RGPD)

| Sujet | Mise en œuvre |
|---|---|
| Inventaire | E-mail, nom affiché, inscriptions, pointages, mots de relais, intentions, sessions et jetons, journaux techniques, journal des exports |
| Consentement | Case obligatoire lors de la première inscription, `Priant.consentAt`. *Écart assumé (T46)* : aucun consentement pour une inscription saisie par un organisateur. |
| Effacement (T19) | `ErasePriant` → `PriantErased`, dans une seule transaction côté Chain : inscriptions futures supprimées ; le priant est anonymisé sur place (e-mail effacé, nom « Un priant », `erasedAt` renseigné), ce qui anonymise ses inscriptions passées tout en gardant les statistiques ; mots de relais et intentions supprimés. Côté Access : sessions, remember-me et passes révoqués, liens invalidés. **Organisateur-priant** : seul le priant est effacé, l'`Account` et le rôle `Organizer` subsistent. |
| Export (T55) | L'export CSV des inscrits fait sortir des données personnelles. Il est réservé au rôle organisateur, journalisé (qui, quand) et accompagné d'un rappel de supprimer le fichier après usage. |
| Conservation (T20) | *Écart assumé* : aucune purge automatique. L'effacement existe déjà, donc une purge pourra être ajoutée sous forme de tâche du Scheduler. |
| Visibilité | Noms visibles des seuls inscrits, e-mails des seuls organisateurs (§6.5) |
| Journaux | Aucun e-mail en clair dans les journaux Monolog : les identifiants sont des UUID |
| Sentry | `send_default_pii: false`, `before_send` qui retire les e-mails, pas de Session Replay |
| Sous-traitants | Hébergeur du VPS, fournisseur e-mail, Sentry, GitHub : accords de sous-traitance à conserver |
| Sauvegardes | Sauvegardes Hetzner (T43, T48) : un priant effacé reste présent dans les sauvegardes jusqu'à leur expiration (7 jours) |

---

## 12. Déploiement et exploitation

### 12.1 Topologie Swarm (T1, T36, T37, T40)

Un VPS **Hetzner Cloud** (T48) **amd64** (T44), de type CX32 ou CPX21 (environ 2 à 4 vCPU, 4 Go), en nœud manager unique. Il n'y a pas de préproduction (T52).

| Service | Image | Réplicas | Points clés |
|---|---|---|---|
| `traefik` | `traefik:v3` | 1 | Ports 80 et 443, ACME (Let's Encrypt), provider Swarm, redirection HTTPS, en-têtes de sécurité, répartition de charge |
| `app` | `ghcr.io/<org>/cdp:<sha>` | 2 | FrankenPHP en mode worker ; mise à jour `start-first` ; sonde de santé `GET /health` ; l'entrypoint attend que le schéma soit à jour (§12.3) |
| `worker` | même image | 1 | Mise à jour `stop-first`. Entrypoint : `doctrine:migrations:migrate`, puis `messenger:consume async_transactional async_bulk scheduler_default --time-limit=3600 --memory-limit=256M` |
| `mercure` | `dunglas/mercure` | 1 | Route `/.well-known/mercure`, abonnés anonymes, JWT de publication |
| `database` | `postgres:18` | 1 | Volume local, mot de passe en secret, réseau interne uniquement |
| `portainer` + `agent` | `portainer/portainer-ce` | 1 + global | Interface sur `portainer.<domaine>`, restreinte par liste d'IP (middleware Traefik) ; seul `/api/webhooks/*` est public |

Le fichier `docker/stack/stack.yaml` décrit la stack. Deux réseaux overlay : `edge` (Traefik ↔ app, mercure, portainer) et `internal` (app, worker ↔ database, mercure).

**Image** (`Dockerfile` multi-étapes) :

1. `front-build` : `node:24`, `pnpm install --frozen-lockfile`, `vite build`.
2. `php-base` : `dunglas/frankenphp:1-php8.5`, extensions (`pdo_pgsql`, `intl`, `opcache`, `apcu`).
3. `prod` : `composer install --no-dev`, copie de `front/dist`, utilisateur non root.

Le Caddyfile envoie `/api*`, `/auth*`, `/unsubscribe*` et `/health` à PHP, et sert les autres chemins en fichiers statiques, avec repli sur `index.html`.

### 12.2 Chaîne de livraison (T33, T38, T40, T44)

```
PR ─► CI (qualité, tests, e2e sur l'image construite) ─► fusion seulement si tout est vert
main ─► build + push GHCR (:sha, :main) ─► déploiement automatique (Portainer)
```

**Chaque fusion sur `main` est déployée en production** (T51, à la place des tags `v*`). Comme il n'y a pas de préproduction (T52), les garde-fous sont :

- protection de branche : PR obligatoire, CI verte obligatoire, e2e Playwright joués sur l'image qui sera déployée ;
- migrations compatibles avec la version précédente ;
- retour arrière immédiat (Portainer ou `failure_action: rollback`) ;
- gel pendant une édition en cours.

Le déploiement passe par les **webhooks de service de Portainer** :

1. La CI vérifie le **gel des déploiements** : si l'édition publique est en cours (`GET /api/editions/current`), le déploiement est refusé, sauf forçage explicite.
2. Elle appelle le webhook du service `worker`, avec le tag de l'image (`?tag=<sha>`). Le worker redémarre et joue les migrations.
3. Elle appelle le webhook du service `app`. Les nouveaux réplicas attendent que le schéma soit à jour avant de démarrer. Grâce au `start-first`, les anciens réplicas continuent de servir pendant ce temps.
4. Elle interroge `GET /health` jusqu'à obtenir `{"version": "<sha>"}`, avec un délai maximal de 10 minutes. En cas d'échec, le retour arrière se fait dans Portainer, ou automatiquement via `update_config.failure_action: rollback`.

**Builds gratuits et rapides** :

- GitHub Actions avec `docker/build-push-action`, BuildKit et un cache `type=gha,mode=max`, plus des caches de montage pour Composer et pnpm. Une modification du back ne reconstruit pas le front, et inversement. Objectif : moins de 5 minutes avec le cache.
- Build natif amd64, sans émulation.
- Coût : minutes illimitées, puisque le dépôt est public.
- GHCR : nettoyage des anciennes versions de l'image (`actions/delete-package-versions`, on garde les 20 dernières).

### 12.3 Migrations (T39)

- **Seul le worker** les joue, au démarrage (`doctrine:migrations:migrate --no-interaction --allow-no-migration`).
- **L'entrypoint de l'app** attend que `doctrine:migrations:up-to-date` réussisse, avec un délai maximal de 5 minutes, avant de démarrer FrankenPHP. La sonde de santé reste un simple appel HTTP.
- **L'ordre de mise à jour** est garanti par la CI : worker d'abord, puis app.
- **Les migrations doivent rester compatibles avec la version précédente** (on ajoute d'abord, on retire au déploiement suivant), car les anciens réplicas tournent encore pendant la mise à jour.
- En dev, `make migrate`.

### 12.4 Secrets (T41)

Les secrets Swarm sont montés en fichiers et lus via `%env(trim:file:…_FILE)%` ou `POSTGRES_PASSWORD_FILE` :

| Secret | Usage |
|---|---|
| `db_password` | PostgreSQL |
| `app_secret` | Symfony. Signe les liens personnels : en changer invalide tous les liens en circulation. |
| `mailer_dsn` | Clé d'API du fournisseur e-mail |
| `mercure_publisher_jwt_key` | Publication Mercure |
| `sentry_dsn` | Sentry |

Un secret Swarm ne se modifie pas. Pour en changer, on crée `<nom>_v2`, on met à jour le service, puis on supprime l'ancien.

### 12.5 Supervision (T42)

- **Sentry, région UE** : back (`sentry/sentry-symfony`) et front (`@sentry/react`), avec données personnelles filtrées (§11) et un échantillonnage des traces à 10 %.
- **Sonde de disponibilité externe**, hébergée hors du VPS (UptimeRobot, Better Stack ou Uptime Kuma ailleurs) : `GET /health` et la page d'accueil, chaque minute. Alerte par e-mail et notification push. Pendant une édition, il faut prévoir une astreinte.
- **`/health`** vérifie que PostgreSQL répond, et renvoie la version (SHA) et l'état du schéma.
- **Journaux** : Monolog en JSON sur stderr, rotation Docker `json-file` (`max-size: 10m`, `max-file: 5`).
- **Alerte sur le transport `failed`** (§7.4).

### 12.6 Sauvegardes (T43)

- **Décision** : uniquement les sauvegardes de l'hébergeur. Chez Hetzner : sauvegardes automatiques quotidiennes (7 conservées, environ +20 % du prix du serveur) et instantanés manuels à la demande.
- **Risques** :
  - la base n'est pas forcément cohérente au moment de la sauvegarde (PostgreSQL rejoue son journal au redémarrage, mais rien ne le garantit) ;
  - les sauvegardes sont chez le même fournisseur que la production ;
  - la granularité est de l'ordre de la journée.
- **Recommandations** :
  - `make backup` (`pg_dump` compressé, copié hors du VPS) avant chaque édition et avant chaque migration délicate ;
  - un test de restauration avant la première édition.

### 12.7 Commandes d'exploitation

| Commande | Usage |
|---|---|
| `app:organizer:create <email>` | Amorçage du premier organisateur |
| `app:account:revoke <email>` | Révoque les sessions, les jetons remember-me et les passes, et invalide les liens d'un compte (T53) |
| `app:edition:delete-test <id>` | Supprime une édition de test, ainsi que ses inscriptions et les priants créés pour l'occasion. Sert à la répétition en production (T52). Refuse toute édition qui a des inscriptions de priants réels. |

---

## 13. Environnement de développement (T35)

**Docker Compose** (`compose.yaml` + `compose.override.yaml`) :

- `php` : FrankenPHP en mode dev (Xdebug, `--watch`) ;
- `node` : Vite en mode dev, qui renvoie `/api`, `/auth` et `/.well-known/mercure` vers `php` pour rester sur la même origine (cookies) ;
- `database` : PostgreSQL 18 ;
- `mercure` ;
- `mailpit` : interface sur le port 8025.

**Makefile** :

| Cible | Effet |
|---|---|
| `make up` / `make down` | Démarrer ou arrêter la stack |
| `make sh` | Shell dans le conteneur PHP |
| `make migrate` / `make migration` | Jouer les migrations / générer une migration |
| `make fixtures` | Jeu de données de démonstration (une édition complète, des textes, des inscriptions) |
| `make openapi` | Exporter le schéma et générer les types front |
| `make qa` | Rector, PHP-CS-Fixer, PHPStan, deptrac, Biome, tsc |
| `make test` / `make test-back` / `make test-front` / `make e2e` | Tests |
| `make backup` | `pg_dump` (dev et prod via contexte SSH) |

---

## 14. Tests et qualité (T32, T34)

### 14.1 Pyramide de tests

| Niveau | Outils | Portée | Base de données |
|---|---|---|---|
| Unitaires back | PHPUnit 13 | Agrégats, VO, `ParisCalendar` (dates du §4.6), `VisibilityPolicy`, `SlotPicker` (aléa avec graine fixe) | Aucune |
| Fonctionnels back | PHPUnit, bus de commandes, repositories InMemory, `EventBusSpy`, `MockClock` | Cas d'usage (§4.4) | InMemory |
| Intégration back | Suite abstraite `*RepositoryTestCase` jouée contre Doctrine et InMemory, plus les ViewFinders | Repositories, projections, contraintes en base | PostgreSQL |
| Acceptation back | `ApiTestCase` (api-platform/test), validation du JSON Schema | Endpoints. **Matrice de visibilité rejouée pour chaque profil** : visiteur, pass appareil, priant vérifié, autre priant, organisateur | PostgreSQL |
| Front | Vitest 5, Testing Library, MSW | Composants, hooks, formulaires, conversion des erreurs 422 | MSW |
| Bout en bout | Playwright, API de Mailpit, axe-core | Parcours complets (ci-dessous) | Stack compose |

Parcours de bout en bout :

- inscription → e-mail → lien → espace personnel ;
- prise d'un créneau vide ;
- parcours organisateur (édition, textes, envoi) ;
- déroulé des 24h, avec une horloge pilotable **uniquement dans l'environnement e2e** ;
- passage à l'heure d'hiver.

### 14.2 Contrôles en CI (bloquants)

- **Back** : Rector (`--dry-run`), PHP-CS-Fixer (`@Symfony:risky`), PHPStan au niveau max (extensions Symfony et Doctrine), deptrac (`deptrac_bc.yaml` et `deptrac_hexa.yaml`), PHPUnit strict (`failOnDeprecation`, `failOnNotice`, `failOnWarning`).
- **Front** : Biome (`biome ci`), `tsc --noEmit`, Vitest. Si Biome ou d'autres outils ne sont pas compatibles avec TypeScript 7, on revient à TypeScript 6.x.
- **Contrat** : contrôle de dérive OpenAPI et types (§5.3).
- **E2E** : Playwright sur l'image construite.

Jobs GitHub Actions : `back` (avec un service Postgres), `front`, `contract`, `image` (build, publication seulement sur `main`), `e2e`, et `deploy` (à chaque fusion sur `main`, voir §12.2).

**Répétition générale sans préproduction (T52)** :

1. Le déroulé complet des 24 heures se joue en e2e, avec l'horloge pilotable, autorisée **uniquement** dans l'environnement e2e.
2. Puis une édition de test en production, avant la programmation de la vraie : quelques organisateurs s'inscrivent, reçoivent les e-mails et testent les liens.
3. L'édition de test est ensuite supprimée avec `app:edition:delete-test`.

---

## 15. Plan de mise en œuvre

Les zones absentes d'apip-ddd concentrent le risque : authentification, e-mails, Scheduler, workers, migrations, front et Swarm. On commence donc par un **squelette de bout en bout**.

| Lot | Contenu |
|---|---|
| **0 — Socle** | Spike de portage de la glue apip-ddd vers API Platform 5 (décision : 5.0 ou 4.4). Monorepo, Docker dev, Makefile, CI qualité. **Squelette** (T54) : compte créé par `app:organizer:create` → « Recevoir mon lien » → e-mail (Mailpit) → lien → session → `/mon-espace`. Tout ce code est définitif. Image de prod, stack Swarm, Traefik, Portainer, premier déploiement par webhook. Sentry et sonde externe. |
| **1 — Programme** | `Edition`, `Slot`, `ParisCalendar` (avec tests du changement d'heure), statut hybride, bibliothèque de textes, attribution, back-office éditions et textes, planning public |
| **2 — Participation** | Inscription complète, pass appareil, `VisibilityPolicy` et matrice d'acceptation, mise en avant des créneaux vides, suggestion, confirmation avec `.ics`, espace personnel, désinscription, anti-abus |
| **3 — Les 24 heures** | « En ce moment » et bilan, Mercure (signaux et mode dégradé), pointage, mots de relais, intentions, modération |
| **4 — Communication** | Campagnes (relance, message libre, invitation), `Delivery`, désabonnement en un clic, rappel de la veille (Scheduler), alerte sur le transport `failed` |
| **5 — Finitions** | Effacement RGPD, version imprimable, audit d'accessibilité (axe-core et lecteurs d'écran), procédure d'exploitation, **répétition générale** : en e2e avec horloge simulée, puis une édition de test en production (§14.2) |

Le calendrier détaillé, avec un jalon d'ouverture des inscriptions vers le 13/11/2026, se trouve dans l'[index du backlog](features/README.md).

---

## 16. Risques et points d'attention

1. **Changement d'heure** (25/10/2026, 28/03/2027 = Pâques). Parades : calcul unique côté back, tests dédiés, données de fuseau à jour dans l'image.
2. **Liens personnels à longue durée de vie** : un e-mail transféré donne accès à la session. Parades : expiration à la fin de l'édition + 7 jours, révocation par `credentialsVersion`, suivi des clics désactivé, `Referrer-Policy`.
3. **Délivrabilité** : le domaine d'envoi est neuf et doit être « chauffé » avant la première relance massive. Il faut SPF, DKIM et DMARC et un taux de plainte bas. Les e-mails de confirmation sont lourds (textes complets et `.ics`).
4. **Fuite par la visibilité** : un seul point de décision (`VisibilityPolicy`), une matrice testée pour chaque profil, pas de cache public, pas de données personnelles dans les journaux ni dans Sentry.
5. **VPS unique** : PostgreSQL et l'hôte sont des points uniques de défaillance pendant 24 heures critiques. Parades : gel des déploiements, sonde externe, astreinte, communication de secours aux priants (e-mail préparé à l'avance).
6. **Sauvegardes limitées aux instantanés (T43)** : la restauration n'est pas garantie et il n'y a pas de copie hors site. Parade minimale : `pg_dump` avant chaque édition.
7. **Écarts RGPD assumés (T20, T46)** : conservation illimitée et inscriptions saisies par un organisateur sans consentement, alors que les intentions peuvent contenir des données de santé (article 9). Décisions à documenter, et à réexaminer.
8. **Portage d'apip-ddd vers API Platform 5** : la glue (ObjectMapper, validateurs de VO, métadonnées) vise la 4.3, et l'auteur prévient que le dépôt n'est pas un modèle à copier tel quel. Parade : spike au lot 0, ne reprendre que le nécessaire, avec des tests.
9. **Migrations jouées par le worker seul (T39)** : si le worker ne démarre pas, les nouveaux réplicas de l'app restent bloqués, mais les anciens continuent de servir. Il faut surveiller les déploiements bloqués et respecter la discipline « on ajoute d'abord, on retire ensuite ».
10. **Hub Mercure à un seul réplica** : s'il tombe, le front repasse en rafraîchissement périodique, sans coupure de service.
11. **Portainer exposé** : l'interface est restreinte par liste d'IP et seuls les webhooks sont publics. L'URL d'un webhook est un secret : on la garde dans les secrets GitHub et on la change si elle fuite.
12. **Concurrence** : modification de l'heure de début alors qu'une inscription arrive, ou double clic. Parades : index unique, verrou optimiste, codes 409 et 422.
13. **Calendrier très serré** : six semaines jusqu'à l'édition, quatre jusqu'à l'ouverture des inscriptions, pour le MVP complet. Le débit dépend de la relecture et des contenus de la Communion. Parades : chemin critique suivi chaque semaine, et features « Cible » (Mercure, pointage, relais, intentions, impression) reportables en dernier recours.
14. **Production sans préproduction, déployée à chaque fusion (T51, T52)** : toute régression part en production. Parades : CI et e2e obligatoires avant la fusion, retour arrière immédiat, gel pendant l'édition, édition de test supprimable.
15. **Domaine d'envoi neuf (T49)** : risque de spam pour les premiers e-mails. Parades : configuration DNS dès l'achat, montée progressive des volumes, liens personnels aussi accessibles via « Recevoir mon lien ».

---

## 17. Points ouverts techniques

1. ~~Fournisseur e-mail~~ → Brevo (T47).
2. **Nom de domaine** : un nouveau domaine dédié (T49), dont le nom reste à choisir et à acheter **cette semaine**.
3. ~~Dépôt public ou privé~~ → public (T50).
4. ~~Hébergeur~~ → Hetzner Cloud (T48), compte et serveur à créer cette semaine.
5. ~~Déclenchement du déploiement~~ → à chaque fusion sur `main` (T51).
6. **Compatibilité de l'outillage avec TypeScript 7** (Biome, plugin TanStack Router) : à vérifier au lot 0.
7. **Palette et typographies** : proposées par Claude, à valider par la Communion.
8. **Astreinte pendant les 24 heures** : qui reçoit les alertes, et par quel canal (à fournir par la Communion).

---

## Annexe A — Journal des décisions techniques

| # | Sujet | Décision |
|---|---|---|
| T1 | Hébergement | VPS + Docker Swarm |
| T2 | Versions | Dernières stables : PHP 8.5, Symfony 8.x, API Platform 5, PostgreSQL 18, React 19, Vite 8 |
| T3 | Dépôt | Monorepo (`api/`, `front/`, `docker/`, `docs/`) |
| T4 | Fidélité à apip-ddd | Pragmatique : InMemory pour les repositories d'écriture seulement, lectures et acceptation sur PostgreSQL, pas de MCP |
| T5 | Bounded contexts | Chain + Access + Shared |
| T6 | Persistance | PostgreSQL 18, attributs Doctrine dans le domaine, UUID v7 |
| T7 | Service de la SPA | Dans l'image FrankenPHP (même origine) ; contrainte : build en CI gratuit et performant |
| T8 | Format d'API | JSON-LD/Hydra, merge-patch |
| T9 | Temps | UTC en base, libellés « heure de Paris » calculés côté back (`ParisCalendar`) |
| T10 | Créneaux | Entité `Slot` interne à `Edition`, `Inscription` agrégat autonome, index unique partiel |
| T11 | Statut d'édition | Hybride : `draft`/`scheduled` stockés, le reste calculé |
| T12 | Ouverture | Automatique seulement pour une édition programmée |
| T13 | Lien personnel | `login_link` de Symfony (signé, durée par lien, révocable, confirmé par POST) |
| T14 | Session | Session Symfony dans PostgreSQL + remember-me persistant ; cookies HttpOnly, Secure, SameSite=Lax |
| T15 | Vue inscrit | Pass appareil (jeton opaque, stocké haché, limité aux inscriptions de l'appareil) |
| T16 | Lien d'invitation | Vaut lien personnel |
| T17 | Visibilité | `VisibilityPolicy` dans le domaine + providers + voters ; e-mails seulement sous `/api/admin` |
| T18 | Anti-abus | Champ piège + limitation par IP et par e-mail (PostgreSQL) + réponse identique ; ALTCHA prêt à activer |
| T19 | Effacement | Anonymisation sur place via `PriantErased` |
| T20 | Conservation | Aucune purge (écart RGPD assumé) |
| T21 | Contrat | `openapi-typescript` + `openapi-fetch` (+ `openapi-react-query`), contrôle de dérive en CI |
| T22 | Routage et données | TanStack Router + TanStack Query |
| T23 | Interface et formulaires | HeroUI (React Aria + Tailwind 4) ; TanStack Form |
| T24 | Back-office | Même SPA, section `/organisation` |
| T25 | Temps réel | Mercure (hub dédié) |
| T26 | Contenu Mercure | Signaux d'invalidation sans données, topic public par édition |
| T27 | Texte riche | Tiptap 3 limité + HTML nettoyé (Symfony HtmlSanitizer), VO `RichText` |
| T28 | .ics et impression | eluceo/ical + impression par le navigateur (`@media print`) |
| T29 | File d'attente | Messenger, transport Doctrine sur PostgreSQL (`use_notify`) |
| T30 | E-mails | Symfony Mailer + fournisseur européen (API), gabarits Twig, suivi des clics désactivé |
| T31 | Planification | Symfony Scheduler dans le worker, tâches à échéance qui rattrapent leur retard |
| T32 | Tests | Pyramide complète : unitaires, fonctionnels, intégration, acceptation, front, e2e avec axe-core |
| T33 | Forge | GitHub (Actions + GHCR) |
| T34 | Qualité | Contrôles d'apip-ddd (Rector, PHP-CS-Fixer, PHPStan max, deptrac, PHPUnit strict) + Biome, tsc, Vitest, Playwright, dérive OpenAPI |
| T35 | Dev local | Docker Compose + Makefile |
| T36 | Topologie | 1 nœud ; app ×2 (`start-first`), worker ×1, mercure ×1, PostgreSQL, Traefik, Portainer |
| T37 | Proxy et TLS | Traefik v3 |
| T38 | Livraison | Déploiement par webhook, à chaque fusion sur `main` (T51) |
| T39 | Migrations | Jouées par le worker seul ; l'app attend un schéma à jour ; mise à jour worker puis app |
| T40 | Outil de déploiement | Portainer CE (webhooks de service) |
| T41 | Secrets | Secrets Swarm (`*_FILE`) |
| T42 | Supervision | Sentry (UE) + sonde externe + journaux JSON + alerte sur le transport `failed` |
| T43 | Sauvegardes | Sauvegardes de l'hébergeur seules (risque documenté, `pg_dump` recommandé avant chaque édition) |
| T44 | Processeur du VPS | amd64 |
| T45 | Durée des créneaux | 30 minutes, fixe (arbitrage fonctionnel) |
| T46 | Inscription saisie par un organisateur | Pas de recueil de consentement (écart RGPD assumé) |
| T47 | Fournisseur e-mail | Brevo (offre gratuite, 300 par jour) |
| T48 | Hébergeur | Hetzner Cloud (CX32/CPX21 amd64, sauvegardes automatiques) |
| T49 | Domaine | Nouveau domaine dédié (à acheter), domaine d'envoi « chauffé » progressivement |
| T50 | Visibilité du dépôt | GitHub public |
| T51 | Déclencheur de déploiement | Chaque fusion sur `main` (remplace les tags `v*`) |
| T52 | Préproduction | Aucune : répétition en e2e (horloge simulée), puis édition de test en production supprimable |
| T53 | Révocation d'un compte | Commande `app:account:revoke`, sans interface |
| T54 | Squelette du lot 0 | Via « Recevoir mon lien » sur un compte créé par commande |
| T55 | Export des inscrits | CSV dans le back-office (`/api/admin/editions/{id}/inscriptions.csv`) |
| T56 | E-mails groupés | Transports `async_transactional` (prioritaire) et `async_bulk`, quota journalier avec report au lendemain |

## Annexe B — Fichiers de référence dans apip-ddd

Chemins relatifs à la racine de [mtarld/apip-ddd](https://github.com/mtarld/apip-ddd), commit `55a4b3b` :

| Sujet | Fichier |
|---|---|
| Kernel et autoconfiguration des handlers | `src/Shared/Infrastructure/Symfony/Kernel.php` |
| Bus de commandes | `src/Shared/Infrastructure/Symfony/Messenger/MessengerCommandBus.php` |
| Identité | `src/Shared/Domain/ValueObject/Identity.php` |
| VO embarqués | `src/Shared/Infrastructure/Doctrine/Mapping/EmbedValueObjectsDriver.php` |
| Agrégat | `src/BookStore/Domain/Model/Book.php` |
| VO auto-validé | `src/BookStore/Domain/ValueObject/BookName.php` |
| Ressource DTO | `src/BookStore/Infrastructure/ApiPlatform/Resource/BookResource.php` |
| ViewFinder (projection) | `src/BookStore/Infrastructure/Doctrine/DoctrineBookViewFinder.php` |
| Validateur de VO | `src/Shared/Infrastructure/Symfony/Validator/` |
| Règles d'architecture | `deptrac_bc.yaml`, `deptrac_hexa.yaml` |
| Configuration API Platform | `config/packages/api_platform.yaml` |
| Docker | `Dockerfile`, `frankenphp/Caddyfile`, `compose*.yaml` |
