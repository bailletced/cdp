# F07 — Attribution des textes aux créneaux

| Lot | Priorité | Estimation |
|---|---|---|
| 1 — Programme | MVP | <!-- est -->12 tâches · 6 h<!-- /est --> |

## Objectif et valeur

Associer un texte de la bibliothèque à chacun des 48 créneaux d'une édition, soit un par un, soit d'un coup grâce à « Répartir dans l'ordre ». Les 48 textes forment une **progression** : c'est le cœur spirituel de l'édition.

Valeur : un organisateur prépare une édition complète en quelques minutes ; les avertissements (texte déjà utilisé, créneaux sans texte au moment de programmer) évitent les oublis sans bloquer.

## Personas concernés

- **Anne, organisatrice** : construit la progression des 48 textes.

## User stories

### US-07.1 — Attribuer un texte à un créneau
En tant qu'**organisatrice**, je veux choisir le texte d'un créneau (ou le retirer) afin d'ajuster la progression.

- **Étant donné** un créneau sans texte, **quand** je choisis un texte par recherche, **alors** le créneau l'affiche aussitôt.
- **Étant donné** un texte déjà attribué à un autre créneau de l'édition, **quand** je l'attribue, **alors** c'est accepté mais un badge « Texte déjà utilisé » m'en avertit.

### US-07.2 — Répartir dans l'ordre
En tant qu'**organisatrice**, je veux sélectionner et ordonner des textes puis les répartir à partir d'un créneau afin de remplir l'édition d'un seul geste.

- **Étant donné** 48 textes ordonnés et le créneau de départ n° 1, **quand** je valide, **alors** le texte n° k est attribué au créneau n° k.
- **Étant donné** 10 textes et le créneau de départ n° 40, **quand** je valide, **alors** seuls les créneaux 40 à 48 sont remplis (9 textes) et l'aperçu me l'a annoncé avant validation.
- **Étant donné** une liste à réordonner, **quand** j'utilise le clavier ou les boutons « Monter » / « Descendre », **alors** je peux tout faire sans glisser-déposer.

### US-07.3 — Être averti à la programmation
En tant qu'**organisatrice**, je veux être avertie s'il reste des créneaux sans texte au moment de programmer afin de ne pas l'oublier (RG-ED-4).

- **Étant donné** 3 créneaux sans texte, **quand** je clique sur « Programmer », **alors** la confirmation indique « 3 créneaux n'ont pas encore de texte » et je peux tout de même programmer.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : §5.6 « Attribution aux créneaux » (un texte par créneau, « Répartir dans l'ordre », avertissement en cas de doublon), RG-ED-4, RG-TX-2 (côté organisateur : créneau sans texte visible).
- **Décisions techniques** : T10 (`Slot` entité interne, `TextId?`) ; §4.4 (`AssignText`, `DistributeTexts`).

## Dépendances

- F05 (éditions, créneaux), F06 (bibliothèque).

## Hors périmètre

- Duplication d'une édition avec ses attributions (RG-ED-6, → F29).
- Plusieurs textes par créneau (exclu par les specs).

## Estimation

<!-- est -->**12 tâches × 0,5 h = 6 h** (6 `back`, 5 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
