# F17 — « En ce moment » et bilan · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 5 | 2,5 h |
| `front` | 4 | 2 h |
| `test` | 1 | 0,5 h |
| **Total** | **10** | **5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Back (US-17.1 à US-17.3)

- [ ] **F17-T01** · `back` `LiveViewFinder` : créneau en cours, créneau suivant, priants (nombre d'inscrits, noms), créneaux écoulés, temps restant + tests d'intégration (`MockClock`) — *Dépend de* : F09-T13 · *Fini quand* : avant, début, milieu et dernier créneau testés.
- [ ] **F17-T02** · `back` `LiveViewFinder` : bilan (créneaux couverts sur 48, priants distincts) + tests d'intégration — *Dépend de* : F17-T01 · *Fini quand* : bilan cohérent avec le tableau de bord (F16).
- [ ] **F17-T03** · `back` `GET /api/editions/{id}/live` (champs selon `VisibilityPolicy`) + tests d'acceptation avant / pendant / après × profils — *Dépend de* : F17-T02, F10-T05 · *Fini quand* : noms `null` pour un visiteur, OpenAPI régénéré.
- [ ] **F17-T04** · `back` Port `ChangeSignal` (Chain/Application/Port) + adaptateur nul par défaut + listeners après commit (`Inscription*` et `PriantRenamed` → `slots`, édition modifiée → `edition`) + tests fonctionnels — *Dépend de* : F14-T03, F14-T05, F16-T08 · *Fini quand* : signaux émis uniquement après validation de la transaction (test).
- [ ] **F17-T05** · `back` Horloge pilotable réservée à `APP_ENV=e2e` (décorateur de `ClockInterface`, réglage par endpoint e2e) + test prouvant son absence en `prod` — *Dépend de* : F00-T17 · *Fini quand* : route absente du routeur de prod (test).

## Front (US-17.1 à US-17.3)

- [ ] **F17-T06** · `front` Bloc « En ce moment » : horaire, priants (nombre ou noms), « Ensuite : … », bascule automatique au changement de créneau (horloge corrigée) + tests (fake timers) — *Dépend de* : F17-T03, F08-T06 · *Fini quand* : bascule testée à la seconde près.
- [ ] **F17-T07** · `front` Barre de progression accessible (`role="progressbar"`, libellé « 7 créneaux sur 48 — 20 h 30 restantes ») + tests — *Dépend de* : F17-T06 · *Fini quand* : valeur et libellé annoncés (test).
- [ ] **F17-T08** · `front` Créneau en cours vide : « Personne n'est inscrit en ce moment — rejoignez la chaîne » + bouton vers `/inscription?creneaux=<en cours>` + test — *Dépend de* : F17-T06, F09-T15 · *Fini quand* : inscription sur le créneau en cours possible de bout en bout.
- [ ] **F17-T09** · `front` Bilan après l'édition (couverture, priants, remerciements) à la place du bloc + test — *Dépend de* : F17-T03 · *Fini quand* : affichage avant / pendant / après testé.

## Parcours (US-17.4)

- [ ] **F17-T10** · `test` E2E déroulé avec horloge pilotable : avant le début, pendant (bascule de créneau), créneau vide → inscription immédiate, après (bilan) — *Dépend de* : F17-T05, F17-T07, F17-T08, F17-T09 · *Fini quand* : parcours vert avec axe-core.
