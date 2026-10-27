# F08 — Planning public (page d'accueil)

| Lot | Priorité | Estimation |
|---|---|---|
| 1 — Programme | MVP | <!-- est -->13 tâches · 6,5 h<!-- /est --> |

## Objectif et valeur

Afficher à tout visiteur l'**édition publique** et la **grille des 48 créneaux**, regroupés par moment de la journée, avec leur état, le nombre d'inscrits et le titre et la source du texte ; permettre de sélectionner plusieurs créneaux pour s'inscrire.

C'est la porte d'entrée de l'application : elle doit être immédiatement compréhensible sur un téléphone, y compris pour une personne âgée, et afficher l'heure locale en complément pour les priants à l'étranger.

## Personas concernés

- **Claire, 29 ans, à Montréal** : arrive par un lien partagé, découvre la chaîne, voit l'heure chez elle.
- **Marie, 74 ans** : repère d'un coup d'œil les créneaux « À pourvoir » de la nuit.
- **Thomas, 41 ans** : sélectionne trois créneaux pour sa famille.

## User stories

### US-08.1 — Découvrir l'édition
En tant que **visiteuse**, je veux voir le nom de la Communion, le titre, les dates et la présentation de l'édition afin de comprendre l'événement.

- **Étant donné** une édition programmée dont l'ouverture est atteinte, **quand** j'ouvre `/`, **alors** je vois son en-tête.
- **Étant donné** deux éditions programmées ouvertes, **quand** j'ouvre `/`, **alors** seule la plus récente (par date de début) est affichée (RG-ED-1).
- **Étant donné** aucune édition publique, **quand** j'ouvre `/`, **alors** je vois une présentation sobre de la Communion et des 24 heures, « La prochaine édition sera bientôt annoncée » et un lien « Recevoir mon lien » (arbitrage Q18).

### US-08.2 — Parcourir la grille
En tant que **visiteuse**, je veux voir les 48 créneaux dans l'ordre chronologique, regroupés en soirée, nuit, matin et après-midi, avec horaire, état, nombre d'inscrits, titre et source du texte afin de choisir.

- **Étant donné** un créneau sans inscription, **quand** il s'affiche, **alors** il porte le libellé « À pourvoir » et une icône en plus de sa couleur.
- **Étant donné** un créneau sans texte, **quand** il s'affiche, **alors** il indique « Le texte de ce créneau sera bientôt disponible » (RG-TX-2).
- **Étant donné** un visiteur non inscrit, **quand** la grille s'affiche, **alors** aucun nom de priant n'est transmis par l'API.

### US-08.3 — Voir l'heure chez moi
En tant que **Claire, à Montréal**, je veux voir l'heure locale en complément afin de ne pas me tromper.

- **Étant donné** un navigateur réglé sur `America/Montreal`, **quand** j'affiche le créneau de 3h00, **alors** je lis « 3h00 (21h00 chez vous) ».
- **Étant donné** un navigateur réglé sur `Europe/Paris`, **quand** j'affiche la grille, **alors** aucune heure locale n'est ajoutée.

### US-08.4 — Ne pas choisir un créneau passé
En tant que **visiteuse**, je veux que les créneaux passés soient grisés et non sélectionnables afin d'éviter une erreur.

- **Étant donné** une horloge de mon téléphone en avance de 10 minutes, **quand** la grille s'affiche, **alors** l'état passé est calculé avec l'heure du serveur (`X-Server-Time`).

### US-08.5 — Sélectionner plusieurs créneaux
En tant que **Thomas**, je veux cocher plusieurs créneaux puis cliquer sur « M'inscrire » afin de m'inscrire en une seule fois.

- **Étant donné** trois créneaux cochés, **quand** je clique sur « M'inscrire (3) », **alors** j'arrive sur `/inscription?creneaux=…` avec ces trois créneaux.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : §5.2 (contenu de la page, grille), RG-ED-1, RG-PL-2 (affichage de l'état), RG-PL-3, RG-PL-4 (aucun nom pour un visiteur), RG-TX-1 (titre et source publics), RG-TX-2 ; §5.3-1 (code couleur + libellé).
- **Décisions techniques** : T8, T9, T11 ; §4.6, §4.7 (requête de l'édition publique), §5.1 (`X-Server-Time`, `Cache-Control`), §8.4.

## Dépendances

- F05 (éditions, statut), F07 (textes des créneaux), F01 (thème).
- Le nombre d'inscrits est réel à partir de F09 ; les noms et la mention « vous » arrivent en F10.

## Hors périmètre

- Bandeau des créneaux vides et bouton « Prendre un créneau vide » (→ F13).
- Bloc « En ce moment » et bilan (→ F17).
- Planning imprimable de l'édition (hors périmètre V1).

## Estimation

<!-- est -->**13 tâches × 0,5 h = 6,5 h** (4 `back`, 8 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
