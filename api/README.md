# API (back-end)

Symfony 8 / API Platform 5, architecture hexagonale. Voir `docs/specs-techniques.md` et `agent.md`.

## Environnement d'exécution

**Aucune commande ne s'exécute en local** : PHP, Composer, PHPUnit, PHPStan, Rector, deptrac… tournent impérativement dans les conteneurs Docker (`docker compose exec php …`, `docker compose run …`). Les versions du poste ne correspondent pas à la CI (PHP 8.4 en local contre 8.5 en CI) : un résultat obtenu en local n'est pas fiable, ni en vert ni en rouge. Voir la règle « Environnement d'exécution » dans `agent.md`.
