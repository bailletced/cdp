# F13 — Mise en avant des créneaux vides

| Lot | Priorité | Estimation |
|---|---|---|
| 2 — Participation | MVP | <!-- est -->8 tâches · 4 h<!-- /est --> |

## Objectif et valeur

Éviter qu'un créneau reste vide — objectif n° 2 de l'application et raison d'être d'une **chaîne ininterrompue**. Trois mécanismes s'ajoutent au code couleur de la grille (F08) : le **bandeau** des créneaux vides, le bouton **« Prendre un créneau vide »** (tirage au hasard qui répartit les priants) et la **suggestion après inscription** (créneau adjacent en priorité).

Valeur : ce sont les leviers les plus efficaces pour combler la nuit ; ils coûtent peu et agissent à chaque visite.

## Personas concernés

- **Claire, visiteuse** : n'a pas d'horaire en tête ; se laisse proposer un créneau.
- **Thomas, 41 ans** : vient de s'inscrire à 22h00 ; accepte en un clic le créneau de 22h30 resté vide.
- **Anne, organisatrice** : voit les vides se résorber.

## User stories

### US-13.1 — Voir combien de créneaux restent à pourvoir
En tant que **visiteuse**, je veux un bandeau « Il reste X créneaux vides sur 48 » avec les horaires concernés afin de savoir où ma prière est la plus utile.

- **Étant donné** 5 créneaux vides, **quand** j'ouvre l'accueil, **alors** le bandeau affiche « Il reste 5 créneaux vides sur 48 » suivi des 5 horaires, cliquables (défilement et focus sur le créneau).
- **Étant donné** l'édition en cours, **quand** le bandeau se calcule, **alors** seuls les créneaux vides **à venir** sont comptés.
- **Étant donné** aucun créneau vide, **quand** j'ouvre l'accueil, **alors** le bandeau affiche « La chaîne est complète. Merci ! ».

### US-13.2 — Prendre un créneau vide au hasard
En tant que **Claire**, je veux qu'on me propose un créneau vide au hasard afin de m'engager sans réfléchir à l'horaire.

- **Étant donné** des créneaux vides à venir, **quand** je clique sur « Prendre un créneau vide », **alors** un créneau m'est proposé avec son horaire et le titre de son texte, et je choisis « Je le prends » (vers le formulaire) ou « Un autre » (nouveau tirage, différent du précédent tant que possible).
- **Étant donné** aucun créneau vide à venir, **quand** j'affiche l'accueil, **alors** le bouton est masqué.

### US-13.3 — Recevoir une suggestion après mon inscription
En tant que **Thomas**, je veux qu'on me suggère un créneau vide proche de celui que je viens de prendre afin de prolonger ma prière d'un clic.

- **Étant donné** que je viens de prendre 22h00 et que 22h30 est vide, **quand** l'écran de confirmation s'affiche, **alors** il me propose 22h30 en priorité (sinon 21h30, sinon un créneau vide au hasard).
- **Étant donné** la suggestion, **quand** je clique sur « Le prendre aussi », **alors** je suis inscrit avec les mêmes informations, sans ressaisie ; **quand** je clique sur « Non merci », **alors** la suggestion disparaît.
- **Étant donné** un créneau qui redevient vide après une désinscription, **quand** la grille se rafraîchit, **alors** il réapparaît dans le bandeau et peut être tiré (RG-EP-1).

## Règles de gestion et décisions couvertes

- **Règles de gestion** : §5.3 (mécanismes 2, 3 et 4), RG-PL-2, RG-EP-1 (réapparition d'un créneau vide).
- **Décisions techniques** : port `SlotPicker` (§4.2), `GET /api/editions/{id}/slot-suggestion` (§5.2), réponse de `POST /api/inscriptions` (§4.4, étape 6) ; tests avec graine fixe (§14.1).

## Dépendances

- F08 (grille), F09 (inscription, confirmation), F01 (`ConfirmDialog`).

## Hors périmètre

- Code couleur de la grille (→ F08).
- Relance « créneaux vides » par e-mail (→ F23).

## Estimation

<!-- est -->**8 tâches × 0,5 h = 4 h** (4 `back`, 3 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
