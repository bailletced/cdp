# ADR 0001 — API Platform 5.0 (et non 4.4)

| | |
|---|---|
| Statut | **Accepté** |
| Date | 17 octobre 2026 |
| Décision liée | T2, T4 (specs techniques), risque n° 8 |
| Tâches | F00-T01, F00-T02, F00-T03 |

## Contexte

Les specs techniques (T2) visent API Platform **5.0**, mais la glue de [mtarld/apip-ddd](https://github.com/mtarld/apip-ddd) (commit `55a4b3b`, 16/09/2026) est écrite pour la **4.3**. Le risque n° 8 demandait de vérifier sur des faits que ce portage ne bloque pas, avant d'écrire du code métier. Le repli prévu était la 4.4.

## Spike

Prototype jetable (hors dépôt, supprimé) : Symfony **8.1.9**, API Platform **5.0.4**, Doctrine ORM 3.7, PHP **8.5.11**, PostgreSQL **18**, PHPUnit 13.4.

On y a repris tel quel le dossier `Shared/` d'apip-ddd (Kernel et autoconfiguration des handlers, `MessengerCommandBus`, `MessengerEventBus`, `Identity`, `AssertValueObject`, ObjectMapper, `EmbedValueObjectsDriver`, `PaginatesWithDoctrine`) et la tranche verticale la plus simple (`Category` : Payload → Processor → Command → Handler, Provider → Resource DTO, VO embarqué), sans les parties MCP, hors périmètre (T4).

| Critère de l'US-00.1 | Résultat |
|---|---|
| Bus de commandes : une commande dispatchée depuis un processor passe dans `doctrine_transaction` | ✅ `POST /api/categories` → 201. Un handler qui persiste puis lève une exception ne laisse **aucune ligne** en base (test de rollback). |
| VO validé | ✅ `name: ""` → 422 avec `violations[].propertyPath`. |
| Ressource DTO alimentée par une View / l'ObjectMapper (`#[Map]`) | ✅ `GET` collection et item ; ressource absente → 404 par `MissingModelException`. |
| VO embarqué (`EmbedValueObjectsDriver`) | ✅ Mapping sans préfixe de colonne, aller-retour sur PostgreSQL 18. |
| Bornes des VO dans l'OpenAPI | ✅ `minLength: 1`, `maxLength: 255` dans le schéma du payload. |
| Event bus : événement retenu jusqu'à la fin de la transaction | ✅ `DispatchAfterCurrentBusStamp` présent sur l'enveloppe. |
| Qualité | ✅ 14 tests, 32 assertions, PHPUnit strict ; **0 déprécation** avec `SYMFONY_DEPRECATIONS_HELPER=max[total]=0`. |

## Décision

**On part sur API Platform 5.0** (>= 5.0.4). Pas de repli sur la 4.4.

## Écarts de portage à connaître

Trois différences avec apip-ddd, toutes mineures et déjà résolues dans le prototype :

1. **Paquet Doctrine séparé** : `composer require api-platform/doctrine-orm` est nécessaire, sinon le conteneur ne compile pas (« Doctrine support cannot be enabled »).
2. **`ApiTestCase` déplacé** : la classe `ApiPlatform\Symfony\Bundle\Test\ApiTestCase` est dépréciée. On utilise `ApiPlatform\Test\ApiTestCase`, du paquet `api-platform/test` (compatible PHPUnit 13).
3. **Type de l'identifiant dans l'OpenAPI** : `#[ApiProperty(jsonSchemaContext: [...])]` n'est plus pris en compte. Il faut `#[ApiProperty(schema: ['type' => 'string', 'format' => 'uuid'])]`, faute de quoi `id` n'a pas de type et `openapi-typescript` génère `unknown`. Note : avec `schema`, l'ancien `readOnly` n'apparaît plus ; `writable: false` reste appliqué côté API.

Le reste de la configuration d'apip-ddd (`exception_to_status`, `formats`/`patch_formats`, `config/services/api_platform.php` pour le convertisseur d'`Identity`) est repris sans changement.

## Conséquences

- Les tâches F00-T08 à F00-T18 reprennent la glue d'apip-ddd en appliquant les trois écarts ci-dessus.
- `stateless: false` (T4, authentification par session) reste à poser dans `api_platform.yaml` ; il n'a pas été éprouvé par le spike.
- À rouvrir si une version 5.x change l'une de ces API.
