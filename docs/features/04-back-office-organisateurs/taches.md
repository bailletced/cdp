# F04 — Back-office : accès et organisateurs · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 3 | 1,5 h |
| `front` | 2 | 1 h |
| `test` | 1 | 0,5 h |
| **Total** | **6** | **3 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Gestion des organisateurs — back (US-04.1, US-04.2)

- [ ] **F04-T01** · `back` Commandes `AddOrganizer` (crée l'`Account` si besoin) et `RemoveOrganizer` (refus s'il ne reste qu'un organisateur) + tests fonctionnels InMemory — *Dépend de* : F02-T03 · *Fini quand* : règle « au moins un » testée, ajout idempotent.
- [ ] **F04-T02** · `back` `OrganizerViewFinder` + ressource `/api/admin/organizers` (GET, POST, DELETE) ; `access_control` `/api/admin/*` réservé à `ROLE_ORGANIZER` + tests d'acceptation (visiteur 401, priant 403, organisateur 200) — *Dépend de* : F04-T01, F02-T12 · *Fini quand* : OpenAPI régénéré, tests par profil verts.
- [ ] **F04-T03** · `back` Test de non-régression : aucune ressource hors `/api/admin` ne sérialise un e-mail (test d'architecture sur les ressources) ; retrait effectif à la requête suivante — *Dépend de* : F04-T02 · *Fini quand* : un champ `email` ajouté hors admin fait échouer le test.

## Back-office — front (US-04.1, US-04.2)

- [ ] **F04-T04** · `front` Section `/organisation` chargée à la demande (route lazy), garde `isOrganizer`, layout et navigation du back-office — *Dépend de* : F02-T19 · *Fini quand* : le bundle back-office n'est pas chargé sur `/` (vérifié au build), priant redirigé.
- [ ] **F04-T05** · `front` Écran « Organisateurs » : liste, ajout par e-mail, retrait avec `ConfirmDialog`, message si dernier organisateur + tests — *Dépend de* : F04-T02, F04-T04 · *Fini quand* : tests Vitest/MSW (succès, 422, refus « dernier »).
- [ ] **F04-T06** · `test` E2E : organisateur → `/organisation` ; priant → refus ; ajout puis retrait d'un organisateur — *Dépend de* : F04-T05 · *Fini quand* : parcours vert avec axe-core.
