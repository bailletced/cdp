# F22 — Modération · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 2 | 1 h |
| `front` | 1 | 0,5 h |
| `test` | 1 | 0,5 h |
| **Total** | **4** | **2 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Back (US-22.1, US-22.2)

- [ ] **F22-T01** · `back` `GET /api/admin/relay-messages?edition=` et `GET /api/admin/intentions?edition=` (ViewFinders admin : contenu, nom affiché, e-mail de l'auteur, date) + tests d'acceptation — *Dépend de* : F20-T04, F21-T06, F04-T02 · *Fini quand* : réservé aux organisateurs, OpenAPI régénéré.
- [ ] **F22-T02** · `back` `DELETE /api/admin/relay-messages/{inscriptionId}` et `DELETE /api/admin/intentions/{id}` (réutilisent `DeleteRelayMessage` / `DeleteIntention`) + tests d'acceptation — *Dépend de* : F22-T01, F20-T02, F21-T04 · *Fini quand* : signal émis, priant refusé.

## Front et parcours (US-22.1, US-22.2)

- [ ] **F22-T03** · `front` Écran « Modération » : onglets mots de relais / intentions, suppression avec `ConfirmDialog`, états vides + tests — *Dépend de* : F22-T02, F04-T04 · *Fini quand* : tests Vitest/MSW, onglets accessibles au clavier.
- [ ] **F22-T04** · `test` E2E : un organisateur supprime une intention → elle disparaît chez un inscrit — *Dépend de* : F22-T03 · *Fini quand* : parcours vert avec axe-core.
