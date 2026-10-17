# F14 — Espace personnel

| Lot | Priorité | Estimation |
|---|---|---|
| 2 — Participation | MVP | <!-- est -->11 tâches · 5,5 h<!-- /est --> |

## Objectif et valeur

Offrir au priant, connecté par son lien personnel, un espace « Mon espace » pour **voir et gérer ses créneaux** de l'édition en cours : **changer son nom à tout moment** (le changement s'applique partout, y compris dans l'historique et les intentions signées), se désinscrire librement avant le début du créneau, ajouter des créneaux, télécharger son fichier agenda unique.

Valeur : la désinscription libre et simple garde le planning fiable (un créneau libéré redevient visible comme vide) ; l'espace est le point d'ancrage des actions des 24 heures (pointage, mot de relais, intentions), ajoutées par les features suivantes.

## Personas concernés

- **Thomas, 41 ans** : renomme « Famille Martin » en « Famille Martin-Dubois », ajoute un créneau, télécharge son agenda.
- **Marie, 74 ans** : se désinscrit d'un créneau qu'elle ne pourra pas honorer.

## User stories

### US-14.1 — Voir mes créneaux
En tant que **priant vérifié**, je veux la liste de mes créneaux de l'édition en cours afin de savoir quand je prie.

- **Étant donné** trois inscriptions, **quand** j'ouvre `/mon-espace`, **alors** je vois mon nom en tête, puis pour chaque créneau l'horaire (avec heure locale si besoin) et un lien vers le texte.
- **Étant donné** aucune inscription, **quand** j'ouvre `/mon-espace`, **alors** je lis « Vous n'avez pas encore de créneau » avec un lien vers le planning.
- **Étant donné** un appareil porteur d'un pass mais sans session, **quand** j'ouvre `/mon-espace`, **alors** on m'invite à recevoir mon lien personnel.

### US-14.2 — Changer mon nom
En tant que **Thomas**, je veux changer le nom sous lequel j'apparais afin de corriger une erreur ou de préciser ma famille.

- **Étant donné** une session ouverte, **quand** je remplace « Famille Martin » par « Famille Martin-Dubois », **alors** le nouveau nom apparaît partout : grille, « En ce moment », fil du relais, intentions signées, historique (RG-IN-2, v0.3).
- **Étant donné** un nom vide ou trop long, **quand** j'enregistre, **alors** l'erreur s'affiche sur le champ.
- **Étant donné** un appareil avec pass mais sans session, **quand** il tente de changer le nom via l'API, **alors** c'est refusé.

### US-14.3 — Me désinscrire
En tant que **Marie**, je veux me désinscrire d'un créneau tant qu'il n'a pas commencé afin de libérer la place.

- **Étant donné** un créneau à venir, **quand** je confirme « Me désinscrire », **alors** une confirmation s'affiche à l'écran, aucun e-mail n'est envoyé, aucune alerte ne part aux organisateurs, et le créneau redevient « À pourvoir » si j'étais seule inscrite (RG-EP-1).
- **Étant donné** un créneau commencé, **quand** je tente de me désinscrire via l'API, **alors** c'est refusé.

### US-14.4 — Ajouter des créneaux et mon agenda
En tant que **Thomas**, je veux ajouter des créneaux depuis mon espace et télécharger mon agenda afin de tout gérer au même endroit.

- **Étant donné** mon espace, **quand** je clique sur « Ajouter des créneaux », **alors** j'arrive sur le planning et le formulaire sera pré-rempli.
- **Étant donné** mon espace, **quand** je clique sur le bouton unique « Ajouter mes créneaux à mon agenda », **alors** je télécharge le `.ics` de tous mes créneaux de l'édition ; il n'y a pas de bouton par créneau (arbitrage Q15).

## Règles de gestion et décisions couvertes

- **Règles de gestion** : §5.5 (accès, « Mes créneaux », « Ajouter des créneaux », changement de nom), RG-EP-1, RG-EP-2, RG-IN-2 (nom modifiable en session uniquement), RG-IN-6, RG-NO-2 ; §5.7 (pas d'e-mail de désinscription).
- **Décisions techniques** : T13, T14, T17 (voter `INSCRIPTION_ACT`) ; §5.2 (`/api/me/inscriptions`, `PATCH /api/me`, `DELETE /api/inscriptions/{id}`), §4.8.

## Dépendances

- F02 (session, `/mon-espace` squelette), F09 (priants, inscriptions), F10 (politique, voter), F11 (`calendar.ics`).
- `RenamePriant` est réutilisée par les organisateurs (F16).

## Hors périmètre

- Modification d'une inscription (il n'y a plus de nombre de personnes ; le nom est porté par le priant).
- Changement d'adresse e-mail.
- Pointage, mot de relais, intentions, « pendant mon créneau » (→ F19, F20, F21) ; historique (→ F29) ; « Mes données » (→ F25, F29).

## Estimation

<!-- est -->**11 tâches × 0,5 h = 5,5 h** (6 `back`, 4 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
