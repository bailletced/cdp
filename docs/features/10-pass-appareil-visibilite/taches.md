# F10 — Pass appareil et politique de visibilité · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 8 | 4 h |
| `front` | 1 | 0,5 h |
| `test` | 2 | 1 h |
| **Total** | **11** | **5,5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Pass appareil (US-10.1)

- [x] **F10-T01** · `back` Agrégat `DevicePass` (Access : hash SHA-256, `InscriptionId[]`, `expiresAt`, `lastSeenAt`) + repository InMemory/Doctrine + migration + tests — *Dépend de* : F09-T04, F02-T02 · *Fini quand* : suite abstraite verte, jeton jamais stocké en clair.
- [x] **F10-T02** · `back` Port `DevicePassIssuer::grant()` (Shared) + adaptateur `DevicePassCookie` : jeton 256 bits, cookie `cdp_pass` (`HttpOnly`, `Secure`, `SameSite=Lax`), ajout au pass existant, expiration fin d'édition + 7 j + tests — *Dépend de* : F10-T01 · *Fini quand* : deux inscriptions successives enrichissent le même pass (test).
- [x] **F10-T03** · `back` `RegisterInscriptionsProcessor` appelle `DevicePassIssuer::grant()` (`Set-Cookie`) + test d'acceptation — *Dépend de* : F10-T02, F09-T10 · *Fini quand* : cookie présent après inscription, absent sur une 422.
- [x] **F10-T04** · `back` `SymfonyCurrentViewer` lit `cdp_pass`, met à jour `lastSeenAt`, ne garde dans `coveredInscriptionIds` que les inscriptions encore actives + tests — *Dépend de* : F10-T02, F02-T12 · *Fini quand* : pass expiré ou inscription annulée → plus aucun droit (test).

## Politique de visibilité (US-10.2, US-10.3)

- [x] **F10-T05** · `back` `VisibilityPolicy` (Chain/Domain/Policy) 1/2 : `canSeeParticipantNames(Viewer, Edition)` et `canSeeIntentions(Viewer, Edition)` (« inscrit de l'édition », organisateur) + tests unitaires — *Dépend de* : F02-T12, F09-T04 · *Fini quand* : table de cas exhaustive par profil.
- [x] **F10-T06** · `back` `VisibilityPolicy` 2/2 : `canReadText(Viewer, Slot)` (y compris éditions passées pour le propriétaire) et `canActOn(Viewer, Inscription)` (priant vérifié propriétaire uniquement) + tests unitaires — *Dépend de* : F10-T05 · *Fini quand* : pass seul → lecture oui, action non (test).
- [x] **F10-T07** · `back` Voter `INSCRIPTION_ACT` déléguant à `canActOn` + tests — *Dépend de* : F10-T06 · *Fini quand* : utilisable via `security: "is_granted('INSCRIPTION_ACT', object)"`.

## Grille selon le profil (US-10.1, US-10.2, US-10.4)

- [x] **F10-T08** · `back` `PlanningViewFinder` : noms, pointages et marque `mine` ; le provider masque à `null` selon la politique + tests d'intégration — *Dépend de* : F10-T04, F10-T05, F09-T13 · *Fini quand* : champ masqué toujours présent et `null`, jamais absent.
- [x] **F10-T09** · `test` Matrice d'acceptation de `GET /api/editions/{id}/slots` et `GET /api/editions/current` × 5 profils (visiteur, pass, priant vérifié, autre priant, organisateur), JSON Schema validé — *Dépend de* : F10-T08 · *Fini quand* : 10 cas verts, réutilisable par les features suivantes (trait de test).
- [ ] **F10-T10** · `front` Grille : noms des priants, coche « a prié » avec libellé accessible, mention « vous » + tests — *Dépend de* : F10-T08, F08-T08 · *Fini quand* : rendu avec et sans noms testé.
- [ ] **F10-T11** · `test` E2E : inscription → noms visibles sur cet appareil ; second navigateur sans pass → aucun nom — *Dépend de* : F10-T10, F09-T21 · *Fini quand* : parcours vert.
