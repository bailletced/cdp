# F26 — Audit d'accessibilité · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `front` | 3 | 1,5 h |
| `test` | 8 | 4 h |
| **Total** | **11** | **5,5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Contrôle automatique (US-26.1)

- [ ] **F26-T01** · `test` axe-core étendu à toutes les routes publiques, de Mon espace et du back-office, avec états significatifs (vide, erreur, dialogue ouvert) — *Dépend de* : F01-T12, F14-T11, F16-T15, F23-T15 · *Fini quand* : inventaire des routes couvert, CI bloquante.

## Audits manuels (US-26.2)

- [ ] **F26-T02** · `test` VoiceOver (iOS Safari) : planning, sélection, formulaire d'inscription, confirmation — rapport d'anomalies — *Dépend de* : F26-T01 · *Fini quand* : anomalies consignées et classées (bloquante, gênante, mineure).
- [ ] **F26-T03** · `test` VoiceOver (iOS Safari) : Mon espace, lecture d'un texte, « En ce moment », « Recevoir mon lien » — *Dépend de* : F26-T01 · *Fini quand* : anomalies consignées et classées.
- [ ] **F26-T04** · `test` NVDA (Windows, Firefox) : parcours publics et Mon espace — *Dépend de* : F26-T01 · *Fini quand* : anomalies consignées et classées.
- [ ] **F26-T05** · `test` NVDA : back-office (éditions, textes et éditeur Tiptap, attribution, inscriptions, envois) — *Dépend de* : F26-T01 · *Fini quand* : anomalies consignées et classées.
- [ ] **F26-T06** · `test` Contrôles manuels : zoom 200 %, mode paysage, navigation au clavier seul, `prefers-reduced-motion`, contrastes sur un téléphone réel en plein jour — *Dépend de* : F26-T01 · *Fini quand* : anomalies consignées et classées.

## Tests avec des priants (US-26.3)

- [ ] **F26-T07** · `test` Tests utilisateurs 1/2 : préparer le scénario (inscription, lien, lecture du texte), recruter 3 priants de plus de 70 ans, conduire les séances sur leur téléphone pendant l'édition de test en production (10–11/11) — *Dépend de* : F14-T11, F13-T08 · *Fini quand* : 3 séances réalisées et notées.
- [ ] **F26-T08** · `test` Tests utilisateurs 2/2 : synthèse des observations, taux de réussite, priorisation avec la Communion — *Dépend de* : F26-T07 · *Fini quand* : liste priorisée validée.

## Corrections (budget)

- [ ] **F26-T09** · `front` Corrections des anomalies bloquantes et gênantes 1/3 : parcours d'inscription et planning — *Dépend de* : F26-T02, F26-T04, F26-T06, F26-T08 · *Fini quand* : anomalies traitées, tests de non-régression ajoutés.
- [ ] **F26-T10** · `front` Corrections 2/3 : Mon espace, lecture, « En ce moment » — *Dépend de* : F26-T03, F26-T09 · *Fini quand* : anomalies traitées, tests ajoutés.
- [ ] **F26-T11** · `front` Corrections 3/3 : back-office — *Dépend de* : F26-T05, F26-T09 · *Fini quand* : anomalies traitées ; les anomalies mineures restantes sont listées.
