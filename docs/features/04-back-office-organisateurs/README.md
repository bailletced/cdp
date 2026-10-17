# F04 — Back-office : accès et organisateurs

| Lot | Priorité | Estimation |
|---|---|---|
| 1 — Programme | MVP | <!-- est -->6 tâches · 3 h<!-- /est --> |

## Objectif et valeur

Ouvrir la section `/organisation` aux seuls organisateurs, avec sa navigation, et leur permettre de gérer eux-mêmes la liste des organisateurs. C'est le prérequis de tous les écrans d'administration (éditions, textes, inscriptions, envois).

La séparation stricte `/api/admin/*` (seules ressources exposant des e-mails, réservées au rôle organisateur) est posée ici et testée pour chaque profil.

> **Note PO** : le §15 ne cite ni la gestion des organisateurs ni le tableau de bord ; ils sont rattachés au lot 1 (accès) et au lot 2 (F16) pour être prêts avant l'ouverture des inscriptions.

## Personas concernés

- **Anne, organisatrice** : accède au back-office, ajoute une autre organisatrice.
- **Thomas, priant** : ne doit jamais voir le back-office ni les e-mails des autres.

## User stories

### US-04.1 — Accéder au back-office
En tant qu'**organisatrice**, je veux accéder à `/organisation` après connexion par mon lien afin d'administrer l'application.

- **Étant donné** une session organisateur, **quand** j'ouvre `/organisation`, **alors** je vois la navigation (Tableau de bord, Éditions, Textes, Inscriptions, Modération, Envois, Organisateurs).
- **Étant donné** un priant connecté, **quand** il ouvre `/organisation` ou appelle `/api/admin/*`, **alors** il est redirigé côté SPA et reçoit 403 côté API.
- **Étant donné** un visiteur, **quand** il appelle `/api/admin/*`, **alors** il reçoit 401.

### US-04.2 — Gérer la liste des organisateurs
En tant qu'**organisatrice**, je veux ajouter ou retirer un organisateur par son e-mail afin de partager la charge.

- **Étant donné** une adresse, **quand** je l'ajoute, **alors** elle apparaît dans la liste (avec « ajouté par » et la date) et peut se connecter par « Recevoir mon lien ».
- **Étant donné** un seul organisateur restant, **quand** on tente de le retirer, **alors** l'action est refusée avec le message « Il doit toujours rester au moins un organisateur ».
- **Étant donné** un organisateur retiré, **quand** il fait sa requête suivante, **alors** il n'a plus accès au back-office.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : §5.9 « Accès » (mêmes droits, au moins un organisateur), module 7 « Organisateurs » ; §4 (Administrer réservé aux organisateurs).
- **Décisions techniques** : T5 (BC Access), T17 (e-mails seulement sous `/api/admin`), T24 (section `/organisation` chargée à la demande).

## Dépendances

- F02 (comptes, `Organizer`, `SecurityUser`, `Viewer`), F01 (layout, composants).

## Hors périmètre

- Rôles distincts entre organisateurs (hors périmètre V1).
- E-mail de bienvenue à un nouvel organisateur (il utilise « Recevoir mon lien »).
- Journal d'audit des actions d'administration.

## Estimation

<!-- est -->**6 tâches × 0,5 h = 3 h** (3 `back`, 2 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
