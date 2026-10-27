# F07 — Attribution des textes aux créneaux · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 7 | 3,5 h |
| `front` | 5 | 2,5 h |
| `test` | 1 | 0,5 h |
| **Total** | **13** | **6,5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Domaine et cas d'usage (US-07.1, US-07.2)

- [x] **F07-T01** · `back` `Edition::assignText(SlotId, ?TextId)` et `Edition::distributeTexts(TextId[], fromRank)` (troncature au créneau 48) + tests unitaires — *Dépend de* : F05-T06, F06-T02 · *Fini quand* : répartition, troncature et retrait testés.
- [x] **F07-T02** · `back` Commandes `AssignText` et `DistributeTexts` (textes existants vérifiés, verrou de version) + tests fonctionnels — *Dépend de* : F07-T01, F06-T03, F05-T09 · *Fini quand* : texte inconnu → erreur, conflit de version testé.
- [x] **F07-T03** · `back` `PUT /api/admin/slots/{id}/text` et `POST /api/admin/editions/{id}/distribute-texts` + tests d'acceptation — *Dépend de* : F07-T02, F05-T14 · *Fini quand* : OpenAPI régénéré, 404/409/422 testés.

## Avertissements (US-07.1, US-07.3)

- [x] **F07-T04** · `back` Détail admin d'édition enrichi : texte de chaque créneau, textes attribués plusieurs fois, nombre de créneaux sans texte + tests d'intégration — *Dépend de* : F07-T03, F05-T13 · *Fini quand* : doublons et manques calculés en SQL et testés.
- [x] **F07-T05** · `back` RG-ED-4 : `schedule` renvoie l'avertissement « X créneaux sans texte » sans bloquer + test d'acceptation — *Dépend de* : F07-T04, F05-T16 · *Fini quand* : programmation réussie avec avertissement dans la réponse.

## Écrans (US-07.1 à US-07.3)

- [x] **F07-T06** · `front` Écran « Attribution » : 48 créneaux groupés par moment avec leur texte (ou « sans texte »), accès depuis le détail d'édition — *Dépend de* : F07-T03, F05-T21 · *Fini quand* : test Vitest/MSW de l'affichage.
- [x] **F07-T07** · `front` Sélecteur de texte par créneau (Autocomplete sur la bibliothèque : titre, source), attribution et retrait, mise à jour optimiste + tests — *Dépend de* : F07-T06, F06-T12 · *Fini quand* : attribution, retrait et conflit 409 testés.
- [x] **F07-T08** · `front` « Répartir dans l'ordre » 1/2 : sélection de textes et liste réordonnable accessible (glisser-déposer React Aria + boutons « Monter » / « Descendre ») — *Dépend de* : F07-T07 · *Fini quand* : réordonnancement possible au clavier seul (test).
- [x] **F07-T09** · `front` « Répartir dans l'ordre » 2/2 : créneau de départ, aperçu des créneaux remplis, envoi, erreurs + tests — *Dépend de* : F07-T08 · *Fini quand* : aperçu conforme à la troncature (test).
- [x] **F07-T10** · `front` Avertissements : badge « Texte déjà utilisé » et confirmation de programmation « X créneaux n'ont pas encore de texte » — *Dépend de* : F07-T04, F07-T05, F07-T09 · *Fini quand* : test Vitest des deux avertissements.

## Données et parcours

- [x] **F07-T11** · `back` Fixtures : attribution des 48 textes à l'édition de démonstration — *Dépend de* : F07-T02, F06-T15 · *Fini quand* : `make fixtures` produit une édition complète.
- [x] **F07-T12** · `test` E2E : répartir 48 textes puis programmer sans avertissement ; vider un créneau puis reprogrammer → avertissement — *Dépend de* : F07-T10 · *Fini quand* : parcours vert avec axe-core.
- [x] **F07-T13** · `back` Commande `app:chain:import-gospel-of-luke` : l'évangile selon saint Luc (Louis Segond 1910) en 48 lectures ajoutées à la bibliothèque, réparties sur une édition avec `--edition`, relançable sans doublon + tests — *Dépend de* : F07-T02 · *Fini quand* : les 1151 versets, dans l'ordre (test) ; édition inconnue refusée.
