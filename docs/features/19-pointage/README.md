# F19 — Pointage « J'ai prié »

| Lot | Priorité | Estimation |
|---|---|---|
| 3 — Les 24 heures | Cible 1re édition | <!-- est -->5 tâches · 2,5 h<!-- /est --> |

## Objectif et valeur

Permettre à chaque priant d'indiquer « J'ai prié » pour chacune de ses inscriptions, du début du créneau jusqu'à 7 jours après la fin de l'édition ; la coche est visible de tous les inscrits et des organisateurs.

Valeur : un signe simple de communion (« la chaîne a tenu ») et un indicateur fiable pour le bilan et le tableau de bord. Non indispensable au déroulé d'une première édition, d'où la priorité « Cible ».

## Personas concernés

- **Marie, 74 ans** : coche « J'ai prié » après son créneau de nuit, le lendemain matin.
- **Thomas, 41 ans** : voit la coche de la personne qui l'a précédé.
- **Anne, organisatrice** : suit le nombre de pointages.

## User stories

### US-19.1 — Indiquer que j'ai prié
En tant que **priant vérifié**, je veux cocher « J'ai prié » pour une inscription afin de témoigner que la chaîne a tenu.

- **Étant donné** mon créneau commencé, **quand** je clique sur « J'ai prié », **alors** la coche apparaît dans mon espace, dans la grille et dans « En ce moment » ; je peux l'annuler.
- **Étant donné** mon créneau pas encore commencé, **quand** j'ouvre mon espace, **alors** le bouton n'est pas proposé (et l'API refuse).
- **Étant donné** l'édition terminée depuis 8 jours, **quand** je tente de pointer, **alors** c'est refusé (RG-LV-1).

### US-19.2 — Voir les pointages
En tant qu'**inscrit**, je veux voir qui a pointé afin de me sentir relié aux autres.

- **Étant donné** un visiteur non inscrit, **quand** il affiche la grille, **alors** il ne voit ni noms ni coches.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : RG-LV-1, RG-LV-2 ; §5.1 (« Terminée : pointage possible 7 jours »).
- **Décisions techniques** : §4.3 (`Inscription.prayedAt`), §4.4 (`MarkPrayed`, `UnmarkPrayed`), §4.8 (`PrayedMarked` → signal), §5.2 (`PUT`/`DELETE /api/inscriptions/{id}/prayed`).

## Dépendances

- F14 (espace personnel), F10 (voter, visibilité), F17 (port `ChangeSignal`, horloge e2e, bloc « En ce moment »).

## Hors périmètre

- Pointage par un organisateur pour autrui (non prévu ; les inscriptions sans e-mail ne sont donc jamais pointées).
- Statistiques détaillées de pointage.

## Estimation

<!-- est -->**5 tâches × 0,5 h = 2,5 h** (3 `back`, 1 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
