# F08 — Planning public · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 4 | 2 h |
| `front` | 6 | 3 h |
| `test` | 1 | 0,5 h |
| **Total** | **11** | **5,5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## API publique (US-08.1, US-08.2)

- [x] **F08-T01** · `back` `PublicEditionViewFinder::current()` (RG-ED-1 : `lifecycle = 'scheduled' AND opens_at <= now ORDER BY starts_at DESC LIMIT 1`) + tests d'intégration (brouillon, programmée future, deux éditions, terminée) — *Dépend de* : F05-T10 · *Fini quand* : les quatre cas sont verts.
- [x] **F08-T02** · `back` `GET /api/editions/current` : titre, présentation, dates et libellés de Paris, statut ; 404 si aucune + tests d'acceptation — *Dépend de* : F08-T01 · *Fini quand* : JSON Schema validé, `Cache-Control: private, no-store`.
- [ ] **F08-T03** · `back` `PlanningViewFinder` : 48 créneaux (rang, libellé, moment, passé / en cours / à venir), titre et source du texte ; champs noms et pointages prévus à `null` + tests d'intégration — *Dépend de* : F08-T01, F07-T01 · *Fini quand* : une seule requête SQL pour la grille (vérifié en test).
- [ ] **F08-T04** · `back` `GET /api/editions/{id}/slots` (404 si l'édition n'est pas publique) + tests d'acceptation — *Dépend de* : F08-T03 · *Fini quand* : brouillon → 404, édition publique → 48 éléments.

## Temps côté client (US-08.3, US-08.4)

- [ ] **F08-T05** · `front` `lib/localTime` : heure locale via `Intl` si le fuseau n'est pas `Europe/Paris` (« 3h00 (21h00 chez vous) ») + tests multi-fuseaux — *Dépend de* : F00-T23 · *Fini quand* : Paris, Montréal et Tokyo testés.
- [ ] **F08-T06** · `front` `lib/serverClock` : décalage estimé depuis `X-Server-Time`, hook `useNow()` + tests (fake timers) — *Dépend de* : F00-T25 · *Fini quand* : une horloge client décalée de 10 min est corrigée (test).

## Écrans (US-08.1 à US-08.5)

- [ ] **F08-T07** · `front` Accueil : en-tête d'édition (nom de la Communion, titre, dates, présentation), état « aucune édition publique » (présentation de la Communion et des 24 heures, « La prochaine édition sera bientôt annoncée », lien « Recevoir mon lien ») — *Dépend de* : F08-T02, F01-T08 · *Fini quand* : test Vitest/MSW des deux états.
- [ ] **F08-T08** · `front` `SlotCard` : horaire (+ heure locale), état par libellé, icône et couleur (« À pourvoir »), nombre d'inscrits, titre et source ou RG-TX-2, créneau passé grisé + tests — *Dépend de* : F08-T04, F08-T05, F08-T06 · *Fini quand* : chaque état testé, contraste AA vérifié.
- [ ] **F08-T09** · `front` Grille groupée par moment de la journée (titres de section, ordre chronologique), rafraîchissement toutes les 60 s et au retour sur l'onglet — *Dépend de* : F08-T08 · *Fini quand* : test de regroupement, `refetchInterval` configuré.
- [ ] **F08-T10** · `front` Sélection multiple : case par créneau à venir, compteur, bouton « M'inscrire (n) » → `/inscription?creneaux=…` + tests — *Dépend de* : F08-T09 · *Fini quand* : créneau passé non sélectionnable, URL testée.
- [ ] **F08-T11** · `test` E2E visiteur : 48 créneaux affichés, fuseau `America/Montreal` → heure locale, sélection → URL d'inscription, axe-core — *Dépend de* : F08-T10, F01-T12 · *Fini quand* : parcours vert.
