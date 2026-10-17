# F29 — Reconduction : duplication, historique, invitations · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 8 | 4 h |
| `front` | 4 | 2 h |
| `test` | 1 | 0,5 h |
| **Total** | **13** | **6,5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Duplication (US-29.1)

- [ ] **F29-T01** · `back` `Edition::duplicate()` + commande `DuplicateEdition` (présentation et attributions par rang, nouvelles dates, sans inscription) + tests — *Dépend de* : F07-T02 · *Fini quand* : RG-ED-6 testée, nouvelle édition en brouillon.
- [ ] **F29-T02** · `back` `POST /api/admin/editions/{id}/duplicate` + tests d'acceptation — *Dépend de* : F29-T01, F05-T14 · *Fini quand* : OpenAPI régénéré, 422 sur dates invalides.
- [ ] **F29-T03** · `front` Action « Dupliquer » dans le détail d'édition (saisie des nouvelles dates) + test — *Dépend de* : F29-T02, F05-T21 · *Fini quand* : redirection vers la nouvelle édition (test).

## Historique (US-29.2)

- [ ] **F29-T04** · `back` Historique : éditions passées du priant dans `GET /api/me` et `GET /api/me/inscriptions?edition=` sur une édition passée + tests — *Dépend de* : F14-T02 · *Fini quand* : un priant ne voit que ses propres éditions passées.
- [ ] **F29-T05** · `front` Onglet « Historique » de Mon espace (éditions passées, créneaux, liens vers les textes) + test — *Dépend de* : F29-T04, F12-T04 · *Fini quand* : état vide et liste testés.

## Invitations (US-29.3)

- [ ] **F29-T06** · `back` `RecipientFinder` type `invitation` (éditions précédentes, consentants, non désabonnés, non effacés, pas encore inscrits) ; envoi refusé si l'édition cible n'est pas ouverte + tests — *Dépend de* : F23-T05 · *Fini quand* : chaque critère d'exclusion testé.
- [ ] **F29-T07** · `back` Lien personnel avec cible (liste blanche : `/`, `/mon-espace`) pour arriver sur le planning + tests — *Dépend de* : F02-T08, F02-T11 · *Fini quand* : cible hors liste blanche ignorée (test de redirection ouverte).
- [ ] **F29-T08** · `back` Jeton de désabonnement signé + `POST /unsubscribe/{token}` (RFC 8058, sans session) + commande `UnsubscribeInvitations` + tests — *Dépend de* : F09-T03 · *Fini quand* : jeton altéré refusé, opération idempotente.
- [ ] **F29-T09** · `back` Gabarit « Invitation » (présentation, lien personnel vers le planning, lien de désabonnement) + en-têtes `List-Unsubscribe` et `List-Unsubscribe-Post` + test — *Dépend de* : F29-T06, F29-T07, F29-T08 · *Fini quand* : en-têtes présents, rendu figé en test.

## Désabonnement (US-29.4)

- [ ] **F29-T10** · `back` `POST /api/me/invitations-unsubscribe` + statut des invitations dans `GET /api/me` + tests — *Dépend de* : F29-T08, F09-T14 · *Fini quand* : statut reflété immédiatement.
- [ ] **F29-T11** · `front` Page de désabonnement (lien de l'e-mail, confirmation) et option « Ne plus recevoir d'invitations » dans « Mes données » + tests — *Dépend de* : F29-T10, F25-T06 · *Fini quand* : les deux chemins testés.
- [ ] **F29-T12** · `front` Type « Invitation » dans l'écran d'envoi (édition cible, aperçu, nombre de destinataires) — *Dépend de* : F29-T09, F23-T14 · *Fini quand* : test Vitest/MSW, refus si l'édition n'est pas ouverte.

## Parcours

- [ ] **F29-T13** · `test` E2E : invitation → lien → planning, priant reconnu et formulaire pré-rempli → désabonnement en un clic → plus d'invitation — *Dépend de* : F29-T11, F29-T12 · *Fini quand* : parcours vert avec axe-core.
