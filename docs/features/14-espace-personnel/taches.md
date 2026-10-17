# F14 — Espace personnel · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 6 | 3 h |
| `front` | 4 | 2 h |
| `test` | 1 | 0,5 h |
| **Total** | **11** | **5,5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Mes créneaux — back (US-14.1)

- [ ] **F14-T01** · `back` `MySpaceViewFinder` : mon nom, mes inscriptions d'une édition (libellés, titre du texte, passé / en cours / à venir, actions possibles) + tests d'intégration — *Dépend de* : F09-T06, F10-T06 · *Fini quand* : actions calculées avec `MockClock` (avant, pendant, après le créneau).
- [ ] **F14-T02** · `back` `GET /api/me/inscriptions?edition=` (édition publique par défaut) ; pass seul ou visiteur → 401 + tests d'acceptation — *Dépend de* : F14-T01 · *Fini quand* : OpenAPI régénéré, profils testés.

## Changer mon nom et se désinscrire — back (US-14.2, US-14.3)

- [ ] **F14-T03** · `back` Commande `RenamePriant` (à tout moment, par le priant ou un organisateur) + événement `PriantRenamed` + tests fonctionnels — *Dépend de* : F09-T03 · *Fini quand* : nom refusé s'il est invalide, événement émis (test `EventBusSpy`).
- [ ] **F14-T04** · `back` `PATCH /api/me` (merge-patch : `displayName`, priant vérifié seulement) + tests d'acceptation par profil — *Dépend de* : F14-T03, F09-T14 · *Fini quand* : pass seul et visiteur refusés, 422 sur nom vide, nouveau nom visible dans la grille (test).
- [ ] **F14-T05** · `back` Commande `CancelInscription` (RG-EP-1 : avant le début du créneau) + événement `InscriptionCancelled` + tests fonctionnels — *Dépend de* : F09-T05 · *Fini quand* : aucun e-mail ni alerte déclenchés (test `EventBusSpy`).
- [ ] **F14-T06** · `back` `DELETE /api/inscriptions/{id}` (403 une fois le créneau commencé) + tests d'acceptation par profil — *Dépend de* : F14-T05, F10-T07 · *Fini quand* : créneau redevenu vide visible dans `editions/current` (test).

## Écrans (US-14.1 à US-14.4)

- [ ] **F14-T07** · `front` `/mon-espace` : mon nom en tête, « Mes créneaux » (horaire + heure locale, lien texte), état vide, invitation à recevoir son lien si pass sans session — *Dépend de* : F14-T02, F02-T19, F08-T05 · *Fini quand* : tests Vitest/MSW des trois états.
- [ ] **F14-T08** · `front` « Changer mon nom » : dialogue avec explication « le nom s'applique partout », erreurs 422 + tests — *Dépend de* : F14-T04, F14-T07 · *Fini quand* : succès et erreur testés, en-tête mis à jour.
- [ ] **F14-T09** · `front` Se désinscrire : confirmation, message à l'écran, planning et bandeau invalidés + tests — *Dépend de* : F14-T06, F14-T07 · *Fini quand* : message annoncé (`aria-live`), liste mise à jour.
- [ ] **F14-T10** · `front` « Ajouter des créneaux » (planning, formulaire pré-rempli) et bouton unique « Ajouter mes créneaux à mon agenda » (`/api/me/calendar.ics`) — *Dépend de* : F14-T07, F11-T06 · *Fini quand* : téléchargement vérifié dans un test e2e léger.

## Parcours

- [ ] **F14-T11** · `test` E2E §14.1 n° 1 : inscription → e-mail → lien → espace → changer mon nom (visible dans la grille) → se désinscrire → créneau « À pourvoir » — *Dépend de* : F14-T08, F14-T09, F11-T08 · *Fini quand* : parcours vert avec axe-core.
