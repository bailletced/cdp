# F12 — Lecture des textes · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 2 | 1 h |
| `front` | 2 | 1 h |
| `test` | 2 | 1 h |
| **Total** | **6** | **3 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## API (US-12.1, US-12.2)

- [x] **F12-T01** · `back` `SlotTextViewFinder` : libellé du créneau, titre, source, corps, pistes, édition + tests d'intégration — *Dépend de* : F07-T01, F06-T04 · *Fini quand* : créneau sans texte → `text: null` (test).
- [x] **F12-T02** · `back` `GET /api/slots/{id}/text` : `canReadText` (pass couvrant ce créneau, priant vérifié inscrit — y compris éditions passées —, organisateur), sinon 404 + tests d'acceptation — *Dépend de* : F12-T01, F10-T06 · *Fini quand* : OpenAPI régénéré, cas nominal et refus testés.
- [x] **F12-T03** · `test` Matrice d'acceptation de `GET /api/slots/{id}/text` × 5 profils (réutilise le trait de F10-T09) — *Dépend de* : F12-T02, F10-T09 · *Fini quand* : 5 profils × (mon créneau, autre créneau) verts.

## Écrans (US-12.1, US-12.2)

- [ ] **F12-T04** · `front` Route `/textes/$slotId` : horaire (+ heure locale), titre, source, corps (`RichTextView`), pistes, RG-TX-2, page « réservé aux priants de ce créneau » avec lien vers l'inscription — *Dépend de* : F12-T02, F06-T11 · *Fini quand* : tests Vitest/MSW des trois états.
- [ ] **F12-T05** · `front` Liens « Lire le texte » depuis la confirmation d'inscription et depuis mes créneaux dans la grille + test — *Dépend de* : F12-T04, F09-T19, F10-T10 · *Fini quand* : liens présents uniquement quand le texte est lisible.
- [ ] **F12-T06** · `test` E2E : inscription → texte lu depuis la confirmation ; second navigateur → page « réservé » — *Dépend de* : F12-T05 · *Fini quand* : parcours vert avec axe-core.
