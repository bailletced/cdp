# F21 — Intentions de prière et « Pendant mon créneau » · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 7 | 3,5 h |
| `front` | 3 | 1,5 h |
| `test` | 1 | 0,5 h |
| **Total** | **11** | **5,5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Domaine et persistance (US-21.1)

- [ ] **F21-T01** · `back` VO `IntentionText` (1–500 caractères) + agrégat `Intention` (`EditionId`, `PriantId`, `anonymous`, `createdAt`) + tests unitaires — *Dépend de* : F09-T02 · *Fini quand* : bornes testées.
- [ ] **F21-T02** · `back` `IntentionRepositoryInterface` + InMemory + Doctrine, migration `intention` + suite abstraite — *Dépend de* : F21-T01, F00-T19 · *Fini quand* : suite verte sur les deux implémentations.

## Cas d'usage et API (US-21.1, US-21.2)

- [ ] **F21-T03** · `back` Commande `PostIntention` (RG-LV-5 : priant vérifié inscrit dans l'édition, de l'ouverture à la fin) + `IntentionPosted` + signal `intentions` + tests fonctionnels — *Dépend de* : F21-T02, F17-T04, F05-T07 · *Fini quand* : fenêtre et droits testés avec `MockClock`.
- [ ] **F21-T04** · `back` Commande `DeleteIntention` (auteur ou organisateur) + `IntentionDeleted` + tests fonctionnels — *Dépend de* : F21-T03 · *Fini quand* : autre priant refusé.
- [ ] **F21-T05** · `back` `POST /api/intentions` et `DELETE /api/intentions/{id}` + tests d'acceptation par profil — *Dépend de* : F21-T04, F10-T05 · *Fini quand* : pass seul → 403, 422 au-delà de 500 caractères.
- [ ] **F21-T06** · `back` `IntentionListViewFinder` + `GET /api/editions/{id}/intentions` (plus récentes en tête, signature = nom actuel du priant ou « Anonyme », marque `mine`) + matrice × profils — *Dépend de* : F21-T02, F10-T05, F10-T09 · *Fini quand* : visiteur → 404, ordre testé.

## Front (US-21.1, US-21.2)

- [ ] **F21-T07** · `front` « Déposer une intention » dans Mon espace : 500 caractères avec compteur, choix « Signer de mon nom » / « Anonyme », avertissement sur les tiers et la santé + tests — *Dépend de* : F21-T05, F14-T07 · *Fini quand* : avertissement lu avant le champ (ordre de lecture testé).
- [ ] **F21-T08** · `front` Page `/intentions` : liste, suppression de mes intentions avec confirmation, état vide, refus pour un visiteur — *Dépend de* : F21-T05, F21-T06 · *Fini quand* : tests Vitest/MSW.

## Pendant mon créneau (US-21.3)

- [ ] **F21-T09** · `back` `MySpaceViewFinder` : créneau en cours du priant et mots de relais du créneau précédent + tests d'intégration — *Dépend de* : F14-T01, F20-T04 · *Fini quand* : cas « pas de créneau en cours » et « créneau précédent vide » testés.
- [ ] **F21-T10** · `front` Mode « Pendant mon créneau » de Mon espace : texte mis en avant, mot de relais précédent, intentions, retour automatique à la fin du créneau — *Dépend de* : F21-T09, F21-T06, F12-T04 · *Fini quand* : bascule testée avec fake timers.

## Parcours

- [ ] **F21-T11** · `test` E2E (horloge pilotable) : dépôt d'une intention ; pendant mon créneau → texte, mot de relais précédent et intention visibles — *Dépend de* : F21-T07, F21-T08, F21-T10, F17-T05 · *Fini quand* : parcours vert avec axe-core.
