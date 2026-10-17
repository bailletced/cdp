# F18 — Temps réel (Mercure) · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 1 | 0,5 h |
| `front` | 2 | 1 h |
| `infra` | 2 | 1 h |
| `test` | 1 | 0,5 h |
| **Total** | **6** | **3 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Infrastructure (US-18.1)

- [ ] **F18-T01** · `infra` Service `mercure` en dev (`compose.yaml`), proxy Vite `/.well-known/mercure`, abonnés anonymes, clé JWT de publication — *Dépend de* : F00-T06 · *Fini quand* : abonnement anonyme possible depuis la SPA en dev.
- [ ] **F18-T02** · `infra` Service `mercure` dans `stack.yaml` (1 réplica, route Traefik `/.well-known/mercure`, secret `mercure_publisher_jwt_key`, réseaux `edge`/`internal`), CSP vérifiée — *Dépend de* : F18-T01, F03-T11 · *Fini quand* : abonnement fonctionnel en production, aucune erreur CSP.

## Publication (US-18.1)

- [ ] **F18-T03** · `back` Adaptateur `MercureChangeSignal` (`symfony/mercure-bundle`, topic `https://<domaine>/editions/{id}`, messages sans données) remplaçant l'adaptateur nul + tests — *Dépend de* : F18-T01, F17-T04 · *Fini quand* : contenu des messages vérifié (ni nom ni e-mail), hub injoignable → erreur journalisée sans faire échouer la requête.

## Abonnement (US-18.1, US-18.2)

- [ ] **F18-T04** · `front` `api/mercure.ts` : `EventSource` sur le topic de l'édition, type de signal → `invalidateQueries` des clés concernées + tests — *Dépend de* : F18-T03, F08-T09 · *Fini quand* : chaque type invalide les bonnes requêtes (test).
- [ ] **F18-T05** · `front` Bascule : polling 60 s coupé quand Mercure est connecté, rétabli en cas d'erreur, reconnexion avec délai croissant + tests — *Dépend de* : F18-T04 · *Fini quand* : les transitions connecté / dégradé sont testées.

## Parcours

- [ ] **F18-T06** · `test` E2E : deux navigateurs, inscription dans l'un → grille à jour dans l'autre en moins de 3 s ; hub arrêté → rafraîchissement périodique — *Dépend de* : F18-T05 · *Fini quand* : parcours vert.
