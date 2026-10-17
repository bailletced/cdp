# F24 — Rappel de la veille et tâches planifiées

| Lot | Priorité | Estimation |
|---|---|---|
| 4 — Communication | MVP | <!-- est -->7 tâches · 3,5 h<!-- /est --> |

## Objectif et valeur

Envoyer automatiquement, **la veille du début à 18h (heure de Paris)**, à chaque priant de l'édition un récapitulatif de tous ses créneaux et de leurs textes, avec le conseil de mettre un réveil pour la nuit et son lien personnel. Le rappel ne part qu'une fois, et rattrape son retard si le worker était arrêté à l'échéance.

La feature met aussi en place le **Symfony Scheduler** dans le worker et les deux tâches d'hygiène et de surveillance : purge des accès expirés (passes, remember-me, sessions, compteurs) et alerte si des messages sont en échec.

Le rappel est classé **transactionnel** (transport `async_transactional`) : il passe avant les campagnes dans le quota journalier de Brevo (F23). Pour la 1re édition, il part le **vendredi 27/11/2026 à 18h**.

Valeur : le rappel est le principal garde-fou contre les oublis, surtout pour les créneaux de nuit ; l'alerte sur les messages en échec évite qu'un incident d'envoi passe inaperçu.

## Personas concernés

- **Marie, 74 ans** : reçoit la veille au soir le rappel de son créneau de 3h00 et règle son réveil.
- **Mainteneur d'astreinte** : est alerté si des e-mails restent bloqués.

## User stories

### US-24.1 — Recevoir le rappel la veille
En tant que **priant**, je veux un rappel la veille à 18h afin de ne pas oublier mes créneaux.

- **Étant donné** une édition qui commence samedi à 20h00, **quand** il est vendredi 18h00 (heure de Paris), **alors** chaque priant ayant un e-mail reçoit un rappel récapitulant tous ses créneaux et leurs textes, avec le conseil de mettre un réveil pour la nuit et son lien personnel.
- **Étant donné** le worker arrêté de 17h50 à 19h10, **quand** il redémarre, **alors** le rappel part à 19h10, une seule fois.
- **Étant donné** le rappel déjà envoyé, **quand** la tâche se relance (toutes les 5 minutes), **alors** rien n'est renvoyé (`reminderSentAt`).

### US-24.2 — Suivre le rappel
En tant qu'**organisatrice**, je veux savoir quand le rappel partira et s'il est parti afin de rassurer les priants.

- **Étant donné** une édition programmée, **quand** j'ouvre son détail, **alors** je lis « Rappel prévu le vendredi 13 mars à 18h00 (heure de Paris) » puis « Rappel envoyé le … » avec l'avancement de l'envoi.

### US-24.3 — Garder le système propre et surveillé
En tant que **mainteneur**, je veux une purge quotidienne des accès expirés et une alerte sur les messages en échec afin de limiter les données inutiles et de réagir vite.

- **Étant donné** des passes appareil expirés, **quand** la purge quotidienne s'exécute, **alors** ils sont supprimés, ainsi que les jetons remember-me expirés, les sessions mortes et les anciens compteurs de limitation.
- **Étant donné** un message dans le transport `failed`, **quand** la vérification (toutes les 15 min) s'exécute, **alors** une alerte part vers Sentry et l'e-mail technique.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : §5.7 (« Rappel la veille », 18h heure de Paris), RG-NO-1, RG-NO-3.
- **Décisions techniques** : T11 (statut sans tâche de fond, échéance par Scheduler), T31 ; §4.6 (`ParisCalendar`, veille 18h), §7.3 (type `eve_reminder`), §7.4, §12.5.

## Dépendances

- F23 (moteur de campagnes), F11 (récapitulatif des créneaux), F05 (échéance), F10 et F15 (passes et compteurs à purger), F03 (Sentry, worker).

## Hors périmètre

- Purge RGPD des données anciennes (écart assumé T20 ; ajoutable plus tard).
- Heure du rappel paramétrable par l'interface (18h fixe en V1, voir point ouvert n° 4 des specs).
- Rappel juste avant le créneau (hors périmètre V1 ; l'alarme `.ics` en tient lieu).

## Estimation

<!-- est -->**7 tâches × 0,5 h = 3,5 h** (5 `back`, 1 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
