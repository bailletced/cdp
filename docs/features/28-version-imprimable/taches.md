# F28 — Version imprimable des textes · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `front` | 2 | 1 h |
| `test` | 1 | 0,5 h |
| **Total** | **3** | **1,5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Impression (US-28.1)

- [ ] **F28-T01** · `front` Route `/textes/$slotId/imprimer` : feuille `@media print`, `@page { size: A4; margin: 2cm }`, gros caractères, horaire, nom de la Communion, titre, source, texte, pistes, coupures de page maîtrisées — *Dépend de* : F12-T04 · *Fini quand* : aperçu avant impression conforme dans Chrome.
- [ ] **F28-T02** · `front` Bouton « Imprimer ou enregistrer en PDF » (`window.print()`) depuis la page du texte et Mon espace + test — *Dépend de* : F28-T01, F14-T07 · *Fini quand* : bouton accessible, test Vitest de l'appel.
- [ ] **F28-T03** · `test` Vérification d'impression et d'enregistrement PDF : Chrome, Firefox, Safari (macOS et iOS), Chrome Android — *Dépend de* : F28-T02 · *Fini quand* : PDF de référence joints à la PR, anomalies corrigées.
