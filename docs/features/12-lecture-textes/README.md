# F12 — Lecture des textes à méditer

| Lot | Priorité | Estimation |
|---|---|---|
| 2 — Participation | MVP | <!-- est -->6 tâches · 3 h<!-- /est --> |

## Objectif et valeur

Remettre à chaque priant, **dès son inscription**, le texte complet et les pistes de méditation de son créneau, dans une page de lecture confortable ; le réserver aux priants de ce créneau et aux organisateurs (RG-TX-1).

Valeur : c'est l'objectif n° 3 de l'application (« remettre à chaque priant le texte de son créneau ») ; la réservation du texte aux inscrits incite à s'inscrire.

## Personas concernés

- **Marie, 74 ans** : lit son texte en gros caractères, juste après s'être inscrite ou au moment de prier.
- **Anne, organisatrice** : relit n'importe quel texte dans son contexte de créneau.
- **Claire, visiteuse** : voit le titre et la source, mais pas le texte complet.

## User stories

### US-12.1 — Lire le texte de mon créneau
En tant que **Marie**, je veux ouvrir le texte de mon créneau depuis la confirmation ou la grille afin de le méditer.

- **Étant donné** une inscription faite depuis cet appareil, **quand** je clique sur « Lire le texte », **alors** je vois l'horaire du créneau, le titre, la source, le corps mis en forme et les pistes de méditation.
- **Étant donné** un créneau sans texte, **quand** j'ouvre la page, **alors** je lis « Le texte de ce créneau sera bientôt disponible » (RG-TX-2).

### US-12.2 — Réserver le texte aux priants du créneau
En tant que **responsable de la Communion**, je veux que le texte complet ne soit lisible que par les priants inscrits sur ce créneau et par les organisateurs.

- **Étant donné** un visiteur, ou un priant inscrit sur un autre créneau, **quand** il ouvre `/textes/{slotId}`, **alors** l'API répond 404 et la page explique que le texte est réservé aux priants de ce créneau, avec un lien pour s'inscrire.
- **Étant donné** un organisateur, **quand** il ouvre n'importe quel texte, **alors** il le lit.
- **Étant donné** un priant vérifié, **quand** il ouvre le texte d'un de ses créneaux d'une édition passée, **alors** il le lit (historique).

## Règles de gestion et décisions couvertes

- **Règles de gestion** : RG-TX-1, RG-TX-2 ; §4 (« Lire le texte complet d'un créneau »).
- **Décisions techniques** : T17 (`canReadText`), T27 (rendu du HTML nettoyé) ; §5.2 (`GET /api/slots/{id}/text`).

## Dépendances

- F10 (`VisibilityPolicy::canReadText`), F06 (`RichTextView`), F07 (attribution), F09 (confirmation).

## Hors périmètre

- Version imprimable (→ F28).
- Mise en avant « pendant mon créneau » (→ F21).

## Estimation

<!-- est -->**6 tâches × 0,5 h = 3 h** (2 `back`, 2 `front`, 2 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
