# F19 — Pointage « J'ai prié » · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 3 | 1,5 h |
| `front` | 1 | 0,5 h |
| `test` | 1 | 0,5 h |
| **Total** | **5** | **2,5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Back (US-19.1 à US-19.3)

- [ ] **F19-T01** · `back` `Inscription::markPrayed()` / `unmarkPrayed()` dans la fenêtre RG-LV-1 (début du créneau → fin de l'édition + 7 j), migration `prayed_at` + tests unitaires — *Dépend de* : F09-T06 · *Fini quand* : bornes de la fenêtre testées à la seconde.
- [ ] **F19-T02** · `back` Commandes `MarkPrayed` / `UnmarkPrayed` + événement `PrayedMarked` + signal `slots` via `ChangeSignal` + tests fonctionnels — *Dépend de* : F19-T01, F17-T04 · *Fini quand* : idempotence (double pointage) testée.
- [ ] **F19-T03** · `back` `PUT` / `DELETE /api/inscriptions/{id}/prayed` (voter `INSCRIPTION_ACT`, 403 hors fenêtre) + tests d'acceptation par profil — *Dépend de* : F19-T02, F10-T07 · *Fini quand* : pass seul refusé, propriétaire accepté.

## Front (US-19.1, US-19.3)

- [ ] **F19-T04** · `front` « J'ai prié » / « Annuler » dans Mon espace (visible dans la fenêtre), coche dans la grille et dans « En ce moment » + tests — *Dépend de* : F19-T03, F14-T07, F17-T06 · *Fini quand* : état du bouton annoncé (`aria-pressed`), invalidations testées.

## Parcours

- [ ] **F19-T05** · `test` E2E (horloge pilotable) : pointer pendant le créneau → coche visible d'un autre inscrit ; à J+8 → bouton absent — *Dépend de* : F19-T04, F17-T05 · *Fini quand* : parcours vert.
