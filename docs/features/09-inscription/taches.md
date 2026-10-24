# F09 — Inscription · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 15 | 7,5 h |
| `front` | 5 | 2,5 h |
| `test` | 1 | 0,5 h |
| **Total** | **21** | **10,5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Domaine Participation (US-09.1 à US-09.3)

- [x] **F09-T01** · `back` VO `DisplayName` (trim, 1–80 caractères, ex. « Marie Dupont », « Famille Dupont ») et `PriantId` + tests unitaires — *Dépend de* : F00-T14 · *Fini quand* : bornes testées, messages d'erreur en français.
- [x] **F09-T02** · `back` Agrégat `Priant` (`Email?` — absent pour un priant saisi par un organisateur —, `DisplayName`, `consentAt?`, `unsubscribedAt?`, `erasedAt?`, `rename()`, `giveConsent()` qui conserve le premier consentement) + tests — *Dépend de* : F09-T01, F00-T12 · *Fini quand* : premier consentement jamais écrasé (test).
- [x] **F09-T03** · `back` `PriantRepositoryInterface` + InMemory + Doctrine, migration `priant` (unique partiel `lower(email) WHERE email IS NOT NULL`) + suite abstraite — *Dépend de* : F09-T02, F00-T19 · *Fini quand* : recherche par e-mail insensible à la casse, plusieurs priants sans e-mail possibles.
- [x] **F09-T04** · `back` Agrégat `Inscription` (`EditionId`, `SlotId`, `PriantId` obligatoire, origine `self` / `organizer`, `version`) + tests — *Dépend de* : F00-T12 · *Fini quand* : invariants testés.
- [x] **F09-T05** · `back` `InscriptionRepositoryInterface` + InMemory (unicité créneau/priant simulée) + suite abstraite — *Dépend de* : F09-T04 · *Fini quand* : doublon rejeté par l'InMemory comme par la base.
- [x] **F09-T06** · `back` Mapping Doctrine, migration `inscription` (FK vers `slot` en `ON DELETE CASCADE` et vers `priant`, `UNIQUE (slot_id, priant_id)`, index) + `DoctrineInscriptionRepository` — *Dépend de* : F09-T05, F09-T03, F05-T10 · *Fini quand* : suite abstraite verte sur PostgreSQL.

## Cas d'usage (US-09.1 à US-09.5)

- [x] **F09-T07** · `back` Commande `RegisterInscriptions` + handler : retrouve le `Priant` par e-mail (nom existant conservé, RG-IN-2) ou le crée avec le nom saisi, consentement, inscriptions, `InscriptionsRegistered` (+ `PriantRegistered` si nouveau) + tests fonctionnels — *Dépend de* : F09-T03, F09-T05, F05-T09 · *Fini quand* : « nom saisi n'écrase pas » et événements testés (`EventBusSpy`).
- [x] **F09-T08** · `back` Règles temporelles du handler : édition ouverte ou en cours, créneau à venir ou en cours, doublon refusé (RG-IN-3) + tests `MockClock` — *Dépend de* : F09-T07 · *Fini quand* : créneau en cours accepté, créneau passé et édition terminée refusés.
- [x] **F09-T09** · `back` Listener Access `PriantRegistered` : crée ou associe l'`Account` si le priant a un e-mail ; port `PriantDirectory` (Shared) pour rattacher un compte organisateur existant + test — *Dépend de* : F09-T07, F02-T02 · *Fini quand* : priant sans e-mail → aucun compte ; organisateur qui s'inscrit → un seul compte.
- [x] **F09-T10** · `back` `RegisterInscriptionsPayload` (`slotIds[]`, nom, e-mail, consentement — obligatoire hors session ou si jamais donné —, `website`) + `RegisterInscriptionsProcessor` → `POST /api/inscriptions` 201/422 + tests — *Dépend de* : F09-T08 · *Fini quand* : bornes publiées dans l'OpenAPI, 422 par champ, règle du consentement testée.
- [x] **F09-T11** · `back` Violation de l'index unique (double clic, concurrence) traduite en 409 « Vous êtes déjà inscrit sur ce créneau » + test — *Dépend de* : F09-T06, F09-T10 · *Fini quand* : deux requêtes simultanées → une 201 et une 409 (test).
- [x] **F09-T12** · `back` RG-ED-3 : `UpdateEditionHandler` refuse un nouveau `startsAt` dès la première inscription + tests — *Dépend de* : F09-T05, F05-T11 · *Fini quand* : refus testé en fonctionnel et en acceptation.

## Lectures (US-09.2, US-09.4)

- [x] **F09-T13** · `back` `PlanningViewFinder` et `editions/current` : nombre d'inscrits par créneau (RG-PL-1), état vide / occupé, compteurs + tests d'intégration — *Dépend de* : F09-T06, F08-T03 · *Fini quand* : une inscription compte pour un (test).
- [x] **F09-T14** · `back` `GET /api/me` enrichi : nom affiché, e-mail, consentement déjà donné (pré-remplissage RG-IN-7, case masquée) + test — *Dépend de* : F09-T06, F02-T16 · *Fini quand* : test d'acceptation priant vérifié.

## Écrans (US-09.1 à US-09.6)

- [x] **F09-T15** · `front` `/inscription?creneaux=…` : récapitulatif des créneaux choisis (libellés, retrait), paramètre validé, créneau passé ou édition fermée signalés — *Dépend de* : F08-T10 · *Fini quand* : tests des paramètres invalides.
- [x] **F09-T16** · `front` Formulaire 1/2 : nom affiché (« Prénom et nom, ou nom de famille »), e-mail, validation Standard Schema bornée par l'OpenAPI + tests — *Dépend de* : F09-T15, F09-T10, F01-T09 · *Fini quand* : bornes et messages testés.
- [x] **F09-T17** · `front` Formulaire 2/2 : consentement toujours affiché et obligatoire hors session, masqué en session s'il est déjà donné (lien vers « Confidentialité »), champ piège `website`, état d'envoi + tests — *Dépend de* : F09-T16 · *Fini quand* : les deux cas du consentement testés, champ piège absent de l'arbre d'accessibilité.
- [x] **F09-T18** · `front` Envoi : erreurs 422 par champ, 409 en message métier, pré-remplissage si priant reconnu, invalidation du planning + tests MSW — *Dépend de* : F09-T17, F09-T14, F09-T11 · *Fini quand* : 201, 409, 422 testés.
- [x] **F09-T19** · `front` `/inscription/confirmation` : récapitulatif des créneaux, « Un e-mail de confirmation vous a été envoyé », emplacements pour le lien vers le texte (F12) et la suggestion (F13) + test — *Dépend de* : F09-T18 · *Fini quand* : rechargement de la page géré (pas d'écran vide).

## Données et parcours

- [x] **F09-T20** · `back` Fixtures : priants (dont « Famille … » et priants sans e-mail), inscriptions multi-créneaux, créneaux restés vides — *Dépend de* : F09-T07, F07-T11 · *Fini quand* : `make fixtures` produit une grille réaliste.
- [ ] **F09-T21** · `test` E2E : choisir 2 créneaux → formulaire → confirmation → grille « occupé » avec compteurs à jour ; nouvelle tentative sur le même créneau → message 409 — *Dépend de* : F09-T19, F09-T13 · *Fini quand* : parcours vert avec axe-core.
