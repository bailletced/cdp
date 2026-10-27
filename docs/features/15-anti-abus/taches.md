# F15 — Protection anti-abus · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 5 | 2,5 h |
| `front` | 1 | 0,5 h |
| **Total** | **6** | **3 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Limitation et champ piège (US-15.1, US-15.2)

- [x] **F15-T01** · `back` Pool de cache DBAL sur PostgreSQL (migration) + limiteurs `symfony/rate-limiter` : inscription (10/h IP, 5/h e-mail), lien (10/h IP, 3/h e-mail) + tests — *Dépend de* : F00-T19 · *Fini quand* : compteurs partagés entre deux processus (test d'intégration).
- [x] **F15-T02** · `back` `trusted_proxies` / `trusted_headers` pour Traefik (IP réelle du client) + test — *Dépend de* : F00-T08 · *Fini quand* : `X-Forwarded-For` pris en compte seulement depuis le réseau `edge` (test).
- [x] **F15-T03** · `back` Limiteurs appliqués : `POST /api/inscriptions` → 429 + `Retry-After` ; `/auth/link-request` → 429 par IP, 202 silencieux par e-mail + tests d'acceptation — *Dépend de* : F15-T01, F15-T02, F09-T10, F02-T15 · *Fini quand* : les quatre seuils testés avec `MockClock`.
- [x] **F15-T04** · `back` Champ piège `website` : réponse identique à un succès, aucun effet, pour l'inscription comme pour le lien + tests — *Dépend de* : F09-T10, F02-T15 · *Fini quand* : aucune ligne créée, aucun message mis en file (tests).

## Défi activable (US-15.3)

- [ ] **F15-T05** · `back` ALTCHA auto-hébergé : endpoint de défi, vérification serveur, activation par `ALTCHA_ENABLED`, configuration exposée à la SPA + tests — *Dépend de* : F15-T03 · *Fini quand* : désactivé par défaut ; activé → requête sans solution refusée (test).
- [ ] **F15-T06** · `front` Widget ALTCHA conditionnel (inscription, lien) et message 429 accessible (« Trop de tentatives, réessayez dans quelques minutes ») + tests — *Dépend de* : F15-T05, F09-T18, F02-T17 · *Fini quand* : les deux modes et le 429 testés avec MSW.
