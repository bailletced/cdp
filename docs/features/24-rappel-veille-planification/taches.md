# F24 — Rappel de la veille et tâches planifiées · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 5 | 2,5 h |
| `front` | 1 | 0,5 h |
| `test` | 1 | 0,5 h |
| **Total** | **7** | **3,5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Planification (US-24.1, US-24.3)

- [ ] **F24-T01** · `back` `ChainSchedule` (Chain/Infrastructure/Scheduler, `stateful` + `lock`) consommé par le worker via `scheduler_default` + test de configuration — *Dépend de* : F00-T18, F03-T07 · *Fini quand* : une tâche factice s'exécute une seule fois avec deux consommateurs (test).

## Rappel de la veille (US-24.1, US-24.2)

- [ ] **F24-T02** · `back` `RecipientFinder` type `eve_reminder` + gabarit « Rappel de la veille » (tous ses créneaux et textes, conseil de réveil pour la nuit, lien personnel) ; livraisons sur `async_transactional`, prioritaires sur le quota journalier + test de rendu — *Dépend de* : F23-T06, F11-T03 · *Fini quand* : un e-mail par priant regroupant tous ses créneaux.
- [ ] **F24-T03** · `back` Commande `SendEveReminders` (toutes les 5 min : échéance passée, édition non commencée, `reminderSentAt` vide → campagne `eve_reminder` + `reminderSentAt`, migration) + tests `MockClock` — *Dépend de* : F24-T01, F24-T02, F05-T04 · *Fini quand* : avant l'échéance → rien ; après → une campagne ; relance → rien.
- [ ] **F24-T04** · `front` Détail d'édition : « Rappel prévu le … (heure de Paris) » puis « Rappel envoyé le … » avec avancement — *Dépend de* : F24-T03, F05-T21 · *Fini quand* : les deux états testés (Vitest/MSW).

## Hygiène et surveillance (US-24.3)

- [ ] **F24-T05** · `back` Tâche quotidienne `PurgeExpiredCredentials` (passes appareil, remember-me, sessions mortes, anciens compteurs) + tests d'intégration — *Dépend de* : F24-T01, F10-T01, F15-T01, F02-T09 · *Fini quand* : seules les données expirées sont supprimées (test).
- [ ] **F24-T06** · `back` Tâche `CheckFailedMessages` (toutes les 15 min : Sentry + e-mail technique si `failed` n'est pas vide) + test — *Dépend de* : F24-T01, F03-T19 · *Fini quand* : alerte émise une fois par incident, pas toutes les 15 min (test).

## Robustesse

- [ ] **F24-T07** · `test` Test d'intégration du rattrapage : worker arrêté à l'échéance puis relancé → un seul rappel ; deux exécutions concurrentes → un seul envoi — *Dépend de* : F24-T03 · *Fini quand* : scénario automatisé vert en CI.
