# F00 — Socle technique · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 16 | 8 h |
| `front` | 6 | 3 h |
| `infra` | 7 | 3,5 h |
| `test` | 1 | 0,5 h |
| `doc` | 1 | 0,5 h |
| **Total** | **31** | **15,5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Spike API Platform 5 (US-00.1)

- [x] **F00-T01** · `back` Spike 1/3 : prototype jetable Symfony 8.1 + API Platform 5.0 avec le `Kernel` et le `MessengerCommandBus` d'apip-ddd (`55a4b3b`) — *Dépend de* : — · *Fini quand* : une commande dispatchée depuis un processor passe dans `doctrine_transaction`, ou les blocages sont notés.
- [x] **F00-T02** · `back` Spike 2/3 : porter `AssertValueObject` et l'ObjectMapper (`#[Map(source: …View::class)]`) sur le prototype — *Dépend de* : F00-T01 · *Fini quand* : une ressource DTO lit une View et l'OpenAPI expose `minLength`/`maxLength`.
- [x] **F00-T03** · `back` Spike 3/3 : porter `EmbedValueObjectsDriver`, rédiger `docs/adr/0001-api-platform.md` (5.0 ou repli 4.4) — *Dépend de* : F00-T02 · *Fini quand* : ADR validé, prototype supprimé.

## Monorepo et environnement de dev (US-00.2)

- [x] **F00-T04** · `infra` Initialiser le monorepo (`api/`, `front/`, `docker/frankenphp`, `docker/stack`, `.editorconfig`, `.gitignore`, `.gitattributes`) — *Dépend de* : F00-T03 · *Fini quand* : arborescence du §3 en place.
- [ ] **F00-T05** · `infra` `Dockerfile` cibles `php-base` (`dunglas/frankenphp:1-php8.5`, `pdo_pgsql`, `intl`, `opcache`, `apcu`, `tzdata`) et `dev` (Xdebug, Composer) — *Dépend de* : F00-T04 · *Fini quand* : `docker build --target dev` passe, `php -m` liste les extensions.
- [ ] **F00-T06** · `infra` `compose.yaml` + `compose.override.yaml` : `php` (worker `--watch`), `database` (PostgreSQL 18), `mailpit` (8025), `node` (Vite, proxy `/api` et `/auth` vers `php`) — *Dépend de* : F00-T05 · *Fini quand* : `docker compose up` démarre tout, SPA et API sur la même origine.
- [ ] **F00-T07** · `infra` `Makefile` : `up`, `down`, `sh`, `migrate`, `migration`, `fixtures`, `openapi`, `qa`, `test`, `test-back`, `test-front`, `e2e`, `help` — *Dépend de* : F00-T06 · *Fini quand* : `make help` liste les cibles, `make up`/`make down` fonctionnent.

## Application Symfony et noyau Shared (US-00.3)

- [ ] **F00-T08** · `back` Créer l'app Symfony 8.1 dans `api/` (API Platform, Doctrine ORM 3 + Migrations, Messenger, Mailer), `date.timezone=UTC`, `stateless: false`, JSON-LD + merge-patch — *Dépend de* : F00-T07 · *Fini quand* : `GET /api` répond dans la stack dev.
- [ ] **F00-T09** · `back` Kernel apip-ddd : `src/{Shared,Chain,Access}/{Domain,Application,Infrastructure}`, `config/services/{shared,chain,access}.php`, autoconfiguration `#[AsCommandHandler]` — *Dépend de* : F00-T08 · *Fini quand* : le conteneur compile, un handler factice est auto-enregistré.
- [ ] **F00-T10** · `back` `CommandInterface`, `CommandBusInterface`, `MessengerCommandBus` (`command.bus` + `doctrine_transaction`) + tests fonctionnels — *Dépend de* : F00-T09 · *Fini quand* : un test prouve le rollback sur exception.
- [ ] **F00-T11** · `back` `event.bus` synchrone avec `DispatchAfterCurrentBusStamp`, `EventBusSpy` pour les tests — *Dépend de* : F00-T10 · *Fini quand* : un test prouve qu'un listener n'est appelé qu'après validation de la transaction.
- [ ] **F00-T12** · `back` VO `Identity` (UUID v7) et `Email` (trim, minuscules, format, longueur max) + tests unitaires — *Dépend de* : F00-T09 · *Fini quand* : cas limites testés, messages d'erreur en français.
- [ ] **F00-T13** · `back` `EmbedValueObjectsDriver` (Shared/Infrastructure/Doctrine/Mapping) + test d'intégration aller-retour d'un VO embarqué — *Dépend de* : F00-T12 · *Fini quand* : test vert sur PostgreSQL 18.
- [ ] **F00-T14** · `back` Validateur `#[AssertValueObject(VO::class)]` publiant `MIN_LENGTH`/`MAX_LENGTH` dans le JSON Schema + tests — *Dépend de* : F00-T12 · *Fini quand* : l'export OpenAPI d'un payload de test contient les bornes.
- [ ] **F00-T15** · `back` ObjectMapper des ressources DTO + `MissingModelException` → 404 (Shared/Infrastructure/ApiPlatform) + tests — *Dépend de* : F00-T09 · *Fini quand* : un provider de test renvoie 200 puis 404.
- [ ] **F00-T16** · `back` Normalisation des erreurs : 422 `violations[]` avec `propertyPath`, 409 (unicité, verrou optimiste), 403 ; `Cache-Control: private, no-store` par défaut + tests d'acceptation — *Dépend de* : F00-T14, F00-T15 · *Fini quand* : chaque code est couvert par un test.
- [ ] **F00-T17** · `back` Horloge PSR-20 injectée (`ClockInterface`, `MockClock` en test) + en-tête `X-Server-Time` sur chaque réponse — *Dépend de* : F00-T09 · *Fini quand* : test d'acceptation vérifiant l'en-tête avec une horloge figée.
- [ ] **F00-T18** · `back` Transports Messenger Doctrine (`use_notify`) : `async_transactional` (confirmation, lien, rappel), `async_bulk` (campagnes) et `failed` ; retry 5 essais (1 min ×3), migration + messages factices — *Dépend de* : F00-T10 · *Fini quand* : `messenger:consume async_transactional async_bulk` traite le transactionnel en priorité (test) ; échec → `failed`.

## Tests et qualité back (US-00.5)

- [ ] **F00-T19** · `back` PHPUnit 13 strict (`failOnDeprecation`, `failOnNotice`, `failOnWarning`), suites unit/functional/integration/acceptance, base de test PostgreSQL, `ApiTestCase` — *Dépend de* : F00-T13 · *Fini quand* : `make test-back` lance les 4 suites.
- [ ] **F00-T20** · `back` Rector, PHP-CS-Fixer (`@Symfony:risky`), PHPStan max (Symfony, Doctrine), deptrac (`deptrac_bc.yaml`, `deptrac_hexa.yaml`) dans `make qa` — *Dépend de* : F00-T19 · *Fini quand* : `make qa` vert ; une dépendance Chain → Access volontaire échoue.

## SPA et contrat OpenAPI (US-00.4)

- [ ] **F00-T21** · `front` Initialiser `front/` : Vite 8, React 19, TypeScript 7 (repli 6.x si Biome ou le plugin TanStack Router sont incompatibles), pnpm, Biome — *Dépend de* : F00-T04 · *Fini quand* : `pnpm build`, `biome ci` et `tsc --noEmit` passent ; choix TS noté dans l'ADR.
- [ ] **F00-T22** · `front` TanStack Router (routes fichiers via le plugin Vite), `main.tsx`, `router.tsx`, route racine et `/` provisoire — *Dépend de* : F00-T21 · *Fini quand* : navigation typée entre deux routes de test.
- [ ] **F00-T23** · `front` Vitest 5 + Testing Library + MSW, cible `make test-front` — *Dépend de* : F00-T22 · *Fini quand* : un test de composant avec un handler MSW passe.
- [ ] **F00-T24** · `front` `make openapi` : `api:openapi:export` → `front/src/api/openapi.json` → `openapi-typescript` → `schema.d.ts` — *Dépend de* : F00-T16, F00-T21 · *Fini quand* : types générés depuis l'API de démonstration.
- [ ] **F00-T25** · `front` `queryClient.ts` et `api/client.ts` (`openapi-fetch` + `openapi-react-query`, credentials same-origin, lecture de `X-Server-Time`) — *Dépend de* : F00-T24 · *Fini quand* : un hook de démonstration est testé avec MSW.
- [ ] **F00-T26** · `front` `api/errors.ts` : 422 → erreurs de champ TanStack Form, 409/403/429 → message métier + tests — *Dépend de* : F00-T25 · *Fini quand* : conversion testée sur des réponses réelles capturées du back.
- [ ] **F00-T27** · `test` Playwright 1.64 sur la stack compose + helper API Mailpit (lire le dernier e-mail d'une adresse, extraire un lien) — *Dépend de* : F00-T06, F00-T22 · *Fini quand* : un test « la page d'accueil répond » passe.

## Intégration continue (US-00.4, US-00.5)

- [ ] **F00-T28** · `infra` GitHub Actions, job `back` : service PostgreSQL 18, cache Composer, `make qa` back, PHPUnit — *Dépend de* : F00-T20 · *Fini quand* : une PR avec un test cassé est rouge.
- [ ] **F00-T29** · `infra` Job `front` : cache pnpm, `biome ci`, `tsc --noEmit`, Vitest — *Dépend de* : F00-T23 · *Fini quand* : une erreur de type fait échouer la PR.
- [ ] **F00-T30** · `infra` Job `contract` : régénère `openapi.json` et `schema.d.ts`, échoue sur différence — *Dépend de* : F00-T24, F00-T28 · *Fini quand* : une dérive volontaire fait échouer la CI.
- [ ] **F00-T31** · `doc` `README.md` racine : démarrage (`make up`, `make fixtures`), conventions (couches, code anglais sauf `Priant`/`Inscription`, définition de « terminé ») — *Dépend de* : F00-T07, F00-T20 · *Fini quand* : un nouveau développeur lance le projet en moins de 15 minutes.
