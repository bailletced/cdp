# F13 — Mise en avant des créneaux vides · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 4 | 2 h |
| `front` | 3 | 1,5 h |
| `test` | 1 | 0,5 h |
| **Total** | **8** | **4 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Back (US-13.1 à US-13.3)

- [ ] **F13-T01** · `back` Port `SlotPicker` + implémentation : adjacent à `near` en priorité (suivant puis précédent), sinon au hasard, exclusions, aléa à graine injectable + tests unitaires — *Dépend de* : F05-T06 · *Fini quand* : tirages déterministes avec graine fixe, priorité d'adjacence testée.
- [ ] **F13-T02** · `back` `EmptySlotsFinder` : créneaux vides à venir (pendant l'édition, seulement à venir) et compteur exposé dans `GET /api/editions/current` + tests d'intégration — *Dépend de* : F09-T13 · *Fini quand* : comptage avant et pendant l'édition testé avec `MockClock`.
- [ ] **F13-T03** · `back` `GET /api/editions/{id}/slot-suggestion?near=&exclude=` (404 s'il n'y a plus de créneau vide) + tests d'acceptation — *Dépend de* : F13-T01, F13-T02 · *Fini quand* : OpenAPI régénéré, cas « plus de vide » testé.
- [ ] **F13-T04** · `back` Réponse de `POST /api/inscriptions` enrichie d'une suggestion adjacente au dernier créneau pris + test — *Dépend de* : F13-T03, F09-T10 · *Fini quand* : suggestion `null` quand la chaîne est complète.

## Front (US-13.1 à US-13.3)

- [ ] **F13-T05** · `front` Bandeau « Il reste X créneaux vides sur 48 » + horaires cliquables (défilement, focus), « La chaîne est complète. Merci ! » + tests — *Dépend de* : F13-T02, F08-T09 · *Fini quand* : les deux états testés, annonce accessible.
- [ ] **F13-T06** · `front` Bouton « Prendre un créneau vide » : dialogue (horaire, titre du texte), « Je le prends » → `/inscription?creneaux=…`, « Un autre » (exclusion du précédent), masqué sans vide + tests — *Dépend de* : F13-T03, F01-T09 · *Fini quand* : focus géré à l'ouverture et à la fermeture du dialogue.
- [ ] **F13-T07** · `front` Suggestion sur la confirmation : « Le prendre aussi » (un clic, mêmes informations conservées en mémoire) / « Non merci » + tests — *Dépend de* : F13-T04, F09-T19 · *Fini quand* : seconde inscription sans ressaisie (test MSW).

## Parcours

- [ ] **F13-T08** · `test` E2E §14.1 : « Prendre un créneau vide » → inscription → suggestion acceptée → bandeau diminué de 2 — *Dépend de* : F13-T05, F13-T06, F13-T07 · *Fini quand* : parcours vert avec axe-core.
