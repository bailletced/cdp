# F28 — Version imprimable des textes

| Lot | Priorité | Estimation |
|---|---|---|
| 5 — Finitions | Cible 1re édition | <!-- est -->3 tâches · 1,5 h<!-- /est --> |

## Objectif et valeur

Proposer pour chaque texte une **mise en page A4 sobre, en gros caractères**, avec l'horaire du créneau et le nom de la Communion, à imprimer ou enregistrer en PDF depuis le navigateur.

Valeur : beaucoup de priants âgés préfèrent prier avec une feuille, surtout la nuit, loin de l'écran. Feature peu coûteuse. Elle n'est pas indispensable à une première édition car l'e-mail de confirmation contient déjà le texte complet (imprimable depuis la messagerie), d'où la priorité « Cible ».

## Personas concernés

- **Marie, 74 ans** : imprime son texte la veille et le pose sur sa table de nuit.

## User stories

### US-28.1 — Imprimer mon texte
En tant que **Marie**, je veux imprimer le texte de mon créneau en gros caractères afin de prier sans écran.

- **Étant donné** un texte que je peux lire, **quand** je clique sur « Imprimer ou enregistrer en PDF », **alors** la boîte d'impression s'ouvre avec une page A4 (marges de 2 cm) comportant l'horaire du créneau, le nom de la Communion, le titre, la source, le texte et les pistes, sans menu ni pied de page de l'application.
- **Étant donné** un texte long, **quand** il dépasse une page, **alors** la coupure ne sépare pas un titre de son paragraphe.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : §5.6 « Version imprimable », §5.5 (accès à la version imprimable depuis l'espace) ; RG-TX-1 (même règle de lecture).
- **Décisions techniques** : T28 ; §10.3 (route `/textes/$slotId/imprimer`, `@media print`, aucun service PDF côté serveur).

## Dépendances

- F12 (page de lecture), F14 (espace personnel).

## Hors périmètre

- Génération de PDF côté serveur (exclue par T28).
- Planning imprimable de l'édition (hors périmètre V1).

## Estimation

<!-- est -->**3 tâches × 0,5 h = 1,5 h** (2 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
