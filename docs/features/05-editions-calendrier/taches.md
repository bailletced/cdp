# F05 — Éditions et calendrier de Paris · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 17 | 8,5 h |
| `front` | 4 | 2 h |
| `test` | 1 | 0,5 h |
| **Total** | **22** | **11 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Calendrier de Paris (US-05.2)

- [x] **F05-T01** · `back` `ParisCalendar::slice()` (Shared/Domain/Time) : 48 créneaux réels de 30 min en UTC, départ à l'heure pleine ou à la demi-heure (RG-ED-2) + tests (édition normale) — *Dépend de* : F00-T17 · *Fini quand* : 48 créneaux contigus, départ invalide rejeté.
- [x] **F05-T02** · `back` `SlotLabel` : « 3h00–3h30 » en heure de Paris, précision « (heure d'été) » / « (heure d'hiver) » des heures ambiguës + test du 25/10/2026 — *Dépend de* : F05-T01 · *Fini quand* : les deux « 2h00 » sont distingués.
- [x] **F05-T03** · `back` Test du 28/03/2027 (créneau 1h30 → 3h00) + `PartOfDay` (soirée 18h–0h, nuit 0h–6h, matin 6h–12h, après-midi 12h–18h) + tests — *Dépend de* : F05-T02 · *Fini quand* : les trois cas obligatoires du §4.6 sont verts.
- [x] **F05-T04** · `back` `ParisCalendar::fromParisLocal()` (heure de Paris sur :00 ou :30 → UTC, résolution automatique : heure ambiguë → première occurrence, heure inexistante → décalée d'une heure) et `eveAt18h()` + tests — *Dépend de* : F05-T01 · *Fini quand* : nuits du 25/10/2026 et du 28/03/2027 testées, aucun refus.

## Domaine Édition (US-05.1, US-05.3, US-05.4)

- [x] **F05-T05** · `back` VO `EditionId`, `EditionTitle` (bornes), `Presentation` (texte multiligne borné) + tests unitaires — *Dépend de* : F00-T12 · *Fini quand* : bornes publiées via constantes, testées.
- [x] **F05-T06** · `back` Agrégat `Edition` + entité interne `Slot` (rang, `startsAt`, `endsAt`, `TextId?`) : la création génère 48 créneaux via `ParisCalendar` + tests — *Dépend de* : F05-T03, F05-T05 · *Fini quand* : invariants testés (48 créneaux, rangs 1–48).
- [x] **F05-T07** · `back` `Edition::statusAt($now)` (`draft`, `scheduled`, `open`, `running`, `finished`), `schedule()` / `unschedule()` + événements de domaine + tests — *Dépend de* : F05-T06 · *Fini quand* : bornes exactes testées (`opensAt`, `startsAt`, `startsAt + 24 h`).
- [x] **F05-T08** · `back` `Edition::reschedule()` : régénération des créneaux, `opensAt` < `startsAt`, garde RG-ED-3 paramétrée (alimentée en F09) + tests — *Dépend de* : F05-T06 · *Fini quand* : régénération et refus testés.
- [x] **F05-T09** · `back` `EditionRepositoryInterface` + InMemory + suite abstraite `EditionRepositoryTestCase` — *Dépend de* : F05-T06 · *Fini quand* : suite verte sur InMemory.
- [x] **F05-T10** · `back` Mapping Doctrine `Edition`/`Slot` (`#[ORM\Version]`), migration `edition`/`slot` (index `slot(edition_id, rank)`), `DoctrineEditionRepository` — *Dépend de* : F05-T09, F00-T13 · *Fini quand* : la suite abstraite passe sur PostgreSQL, migration réversible.

## Cas d'usage (US-05.1, US-05.3, US-05.4)

- [x] **F05-T11** · `back` Commandes `CreateEdition` et `UpdateEdition` + handlers + tests fonctionnels (`MockClock`, `EventBusSpy`) — *Dépend de* : F05-T08, F05-T09, F00-T11 · *Fini quand* : création, modification et conflit de version testés.
- [x] **F05-T12** · `back` Commandes `ScheduleEdition` et `UnscheduleEdition` + handlers + tests fonctionnels — *Dépend de* : F05-T07, F05-T09 · *Fini quand* : transitions autorisées et interdites testées.

## API d'administration (US-05.5)

- [x] **F05-T13** · `back` `AdminEditionViewFinder` : liste et détail (statut calculé en SQL, 48 créneaux libellés avec moment de la journée) + tests d'intégration — *Dépend de* : F05-T10 · *Fini quand* : statut SQL cohérent avec `statusAt()` sur 5 dates de test.
- [x] **F05-T14** · `back` `GET /api/admin/editions` (collection et item) : ressource, provider, ObjectMapper + tests d'acceptation — *Dépend de* : F05-T13, F04-T02 · *Fini quand* : JSON Schema validé, 404 sur id inconnu.
- [x] **F05-T15** · `back` `POST` / `PATCH /api/admin/editions` : payloads `AssertValueObject`, processors, 422 (minutes ≠ 00/30, ouverture après le début), 409 (version) ; la réponse indique le début retenu et son libellé + tests — *Dépend de* : F05-T11, F05-T14 · *Fini quand* : chaque erreur a son `propertyPath`, résolution automatique visible dans la réponse.
- [x] **F05-T16** · `back` `POST /api/admin/editions/{id}/schedule` et `/unschedule` + tests d'acceptation — *Dépend de* : F05-T12, F05-T14 · *Fini quand* : statut renvoyé à jour.
- [x] **F05-T17** · `back` Fixtures (via le bus de commandes) : une édition ouverte calée sur « maintenant », un brouillon ; cible `make fixtures` — *Dépend de* : F05-T11, F05-T12 · *Fini quand* : `make fixtures` rejouable sans erreur.

## Écrans (US-05.1 à US-05.5)

- [x] **F05-T18** · `front` Écran « Éditions » : liste (titre, dates en heure de Paris, statut en libellé) — *Dépend de* : F05-T14, F04-T04 · *Fini quand* : test Vitest/MSW, état vide prévu.
- [x] **F05-T19** · `front` Champ réutilisable `ParisDateTimeField` (date + heure en heure de Paris via `@internationalized/date`, fuseau `Europe/Paris` explicite, indépendant du fuseau du navigateur, affichage du début retenu s'il a été ajusté) + tests — *Dépend de* : F01-T09 · *Fini quand* : même valeur envoyée quel que soit le fuseau simulé (tests Paris, Montréal).
- [x] **F05-T20** · `front` Formulaire de création/modification (titre, présentation, début et ouverture en heure de Paris), erreurs 422/409 + tests — *Dépend de* : F05-T15, F05-T18, F05-T19 · *Fini quand* : « heure de Paris » explicite dans les libellés, erreurs sur les bons champs.
- [x] **F05-T21** · `front` Détail d'une édition : statut, « Programmer » / « Repasser en brouillon » (confirmation), 48 créneaux groupés par moment — *Dépend de* : F05-T16, F05-T18 · *Fini quand* : test Vitest des actions.
- [ ] **F05-T22** · `test` E2E organisateur : créer une édition, la programmer, la retrouver « Programmée » dans la liste — *Dépend de* : F05-T20, F05-T21 · *Fini quand* : parcours vert avec axe-core.
