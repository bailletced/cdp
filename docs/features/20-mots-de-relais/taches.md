# F20 — Mots de relais · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 4 | 2 h |
| `front` | 2 | 1 h |
| `test` | 1 | 0,5 h |
| **Total** | **7** | **3,5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Écrire un mot — back (US-20.1)

- [ ] **F20-T01** · `back` VO `RelayMessage` (texte brut, 1–500 caractères) + `Inscription::writeRelayMessage()` / `deleteRelayMessage()` (fenêtre RG-LV-3), migration + tests unitaires — *Dépend de* : F09-T06 · *Fini quand* : bornes et fenêtre testées.
- [ ] **F20-T02** · `back` Commandes `WriteRelayMessage` / `DeleteRelayMessage` (auteur ; organisateur pour la suppression) + événement `RelayMessageWritten` + signal `relay` + tests fonctionnels — *Dépend de* : F20-T01, F17-T04 · *Fini quand* : droits et signal testés.
- [ ] **F20-T03** · `back` `PUT` / `DELETE /api/inscriptions/{id}/relay-message` (voter `INSCRIPTION_ACT`) + tests d'acceptation par profil — *Dépend de* : F20-T02, F10-T07 · *Fini quand* : 422 au-delà de 500 caractères, pass seul refusé.

## Fil du relais — back (US-20.2)

- [ ] **F20-T04** · `back` `RelayFeedViewFinder` + `GET /api/editions/{id}/relay-messages` (inscrits et organisateurs, ordre des créneaux) + matrice × profils — *Dépend de* : F20-T01, F10-T05, F10-T09 · *Fini quand* : visiteur → 404, inscrit → fil ordonné.

## Front (US-20.1, US-20.2)

- [ ] **F20-T05** · `front` Écrire, modifier, supprimer mon mot de relais dans Mon espace (compteur de caractères accessible) + tests — *Dépend de* : F20-T03, F14-T07 · *Fini quand* : compteur annoncé aux lecteurs d'écran, erreurs testées.
- [ ] **F20-T06** · `front` Page `/relais` : fil classé par créneau, état vide, refus pour un visiteur ; lien depuis l'accueil pour les inscrits — *Dépend de* : F20-T04, F01-T08 · *Fini quand* : tests Vitest/MSW des trois états.

## Parcours

- [ ] **F20-T07** · `test` E2E (horloge pilotable) : mot écrit pendant le créneau → visible dans `/relais` depuis un autre inscrit ; visiteur → refus — *Dépend de* : F20-T05, F20-T06, F17-T05 · *Fini quand* : parcours vert avec axe-core.
