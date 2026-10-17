# F10 — Pass appareil et politique de visibilité

| Lot | Priorité | Estimation |
|---|---|---|
| 2 — Participation | MVP | <!-- est -->11 tâches · 5,5 h<!-- /est --> |

## Objectif et valeur

Après une inscription, l'appareil qui l'a faite bénéficie de la **vue inscrit en lecture** (noms des priants, pointages, textes de ses créneaux) sans que la personne ait à ouvrir son e-mail ; en revanche, **aucune action** au nom du priant n'est possible sans session ouverte par le lien personnel (RG-IN-6). Cela protège contre quelqu'un qui saisirait l'e-mail d'un autre.

La feature introduit la `VisibilityPolicy`, **point de décision unique** de la matrice de droits (§4 des specs fonctionnelles), et la rejoue en tests d'acceptation pour chaque profil : c'est la principale parade au risque de fuite de données personnelles.

## Personas concernés

- **Marie, 74 ans** : vient de s'inscrire, voit aussitôt qui prie avant et après elle.
- **Claire, visiteuse non inscrite** : ne voit jamais un nom.
- **Thomas, priant vérifié** : voit les noms et agit sur ses inscriptions.

## User stories

### US-10.1 — Voir la chaîne juste après m'être inscrite
En tant que **Marie**, je veux voir les noms des priants dès mon inscription sur cet appareil afin de me sentir reliée à la chaîne.

- **Étant donné** une inscription faite depuis mon téléphone, **quand** je reviens sur la grille, **alors** je vois les noms complets, les coches de pointage et la mention « vous » sur mes créneaux.
- **Étant donné** que je me désinscris de mon seul créneau, **quand** je recharge la grille, **alors** je ne vois plus les noms (les droits sont recalculés à chaque requête).

### US-10.2 — Ne jamais exposer les noms à un visiteur
En tant que **responsable de la Communion**, je veux que les noms ne soient visibles que des inscrits de l'édition en cours afin de respecter la confidentialité (RG-PL-4).

- **Étant donné** un visiteur sans pass ni session, **quand** il appelle `GET /api/editions/{id}/slots`, **alors** les champs de noms et de pointages valent `null`.
- **Étant donné** un priant vérifié qui n'a aucune inscription dans l'édition en cours, **quand** il affiche la grille, **alors** il ne voit pas les noms.

### US-10.3 — Agir seulement avec une session
En tant que **priant**, je veux que personne ne puisse modifier mes inscriptions sans mon lien personnel.

- **Étant donné** un appareil porteur d'un pass mais sans session, **quand** il tente de modifier, d'annuler ou de pointer une inscription, **alors** l'API refuse (403).
- **Étant donné** un priant vérifié, **quand** il tente d'agir sur l'inscription d'un autre, **alors** l'API refuse.

### US-10.4 — Une matrice testée
En tant que **mainteneur**, je veux que la matrice soit rejouée automatiquement pour les profils visiteur, pass appareil, priant vérifié, autre priant et organisateur afin qu'aucune régression ne fasse fuir de données.

- **Étant donné** un nouveau champ ajouté à une ressource, **quand** il n'est pas couvert par la politique, **alors** la matrice d'acceptation échoue.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : §4 (matrice des droits), RG-IN-6, RG-PL-4, RG-LV-2 (visibilité des pointages), RG-TX-1 (règle de lecture, appliquée en F12), RG-EP-2 (e-mails jamais affichés aux autres priants).
- **Décisions techniques** : T15, T17 ; §6.1, §6.4, §6.5 ; §14.1 (matrice rejouée par profil).

## Dépendances

- F09 (inscriptions), F02 (`Viewer`, session).
- Utilisée par F12, F14, F17, F19 à F22.

## Hors périmètre

- Purge des passes expirés (→ F24, tâche `PurgeExpiredCredentials`).
- Transfert du pass d'un appareil à l'autre (on passe par le lien personnel).

## Estimation

<!-- est -->**11 tâches × 0,5 h = 5,5 h** (8 `back`, 1 `front`, 2 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
