# CLAUDE.md

Application web « 24 heures de prière » de la **Communion de prière Notre-Dame du Mont-Carmel** : des priants s'inscrivent sur des créneaux de 30 minutes pour former une chaîne de prière ininterrompue de 24 heures.

1re édition : **samedi 28/11/2026 20h → dimanche 29/11/2026 20h**, ouverture des inscriptions vers le 13/11/2026.

## Documentation de référence

| Document | Contenu |
|---|---|
| `docs/specs-fonctionnelles.md` | Besoin, règles de gestion (`RG-xx`), journal des décisions fonctionnelles |
| `docs/specs-techniques.md` | Architecture et décisions techniques (`T1`–`T56`) : Symfony 8 / API Platform 5 en hexagonal (inspiré de mtarld/apip-ddd), SPA React, Docker Swarm |
| `docs/features/README.md` | Index du backlog : analyse PO, rétroplanning, conventions, définition de « terminé », journal des arbitrages |

En cas de contradiction entre ces documents et une demande, signale-la au lieu de trancher seul.

## Les features sont rangées par dossier

Le backlog est découpé en **une feature par dossier** dans `docs/features/`, numérotés dans l'ordre de réalisation :

```
docs/features/
├── README.md                    index, rétroplanning, conventions
├── 00-socle-technique/
│   ├── README.md                le besoin : user stories, critères d'acceptation, RG et décisions T concernées
│   └── taches.md                les tâches à faire (≈ 30 min chacune pour un humain)
├── 01-theme-accessibilite/
└── …
```

- Une tâche s'identifie par `F<NN>-T<NN>` (ex. `F09-T03`). Respecte ses dépendances (*Dépend de*) et son critère (*Fini quand*), en plus de la définition de « terminé » de l'index.
- Quand une tâche est terminée, coche-la (`- [x]`) dans le `taches.md` de sa feature, dans le même commit que le code.
- Avant de commencer une feature, lis son `README.md` : les critères d'acceptation des user stories servent de tests d'acceptation.
- Si une tâche s'avère nettement plus grosse que prévu, découpe-la dans `taches.md` (nouveaux identifiants en fin de liste) plutôt que de livrer un gros lot.

## Méthode : Extreme Programming (Kent Beck)

On travaille en **Extreme Programming**, tel que décrit par Kent Beck dans *Extreme Programming Explained*.

### Valeurs

Communication, simplicité, feedback, courage et respect. Concrètement :
- tu dis ce que tu fais, ce qui bloque et ce qui est incertain ;
- tu fais la chose la plus simple qui puisse marcher ;
- tu cherches un retour rapide : tests, CI, déploiement, relecture ;
- tu oses refactorer et jeter du code qui n'a plus lieu d'être.

### Pratiques appliquées au projet

- **Développement piloté par les tests (TDD)** : pour toute tâche de code, écris d'abord un test qui échoue (rouge), puis le minimum de code pour le faire passer (vert), puis refactore (refactor). Ne code pas de comportement sans test qui l'exige. Le test d'acceptation de la user story est écrit en premier quand c'est possible.
- **Conception simple** (les quatre règles de Beck, par ordre de priorité) :
  1. passe tous les tests ;
  2. exprime clairement l'intention ;
  3. ne contient pas de duplication ;
  4. a le moins d'éléments possible.

  Pas d'anticipation : YAGNI, rien pour un besoin qui n'est pas dans une tâche.
- **Refactoring continu** : on améliore le code à chaque passage, toujours avec les tests au vert, et jamais dans le même pas qu'un changement de comportement.
- **Petits pas et intégration continue** : une tâche, ou quelques tâches très liées, par PR. On intègre plusieurs fois par jour et `main` est toujours vert. Chaque fusion sur `main` part en production (T51), donc chaque changement doit être sûr seul.
- **Build en dix minutes** : la CI et `make test` restent rapides. Si un test ralentit la boucle de feedback, signale-le.
- **Petites livraisons** : chaque feature livre un incrément démontrable en production, dans l'ordre du rétroplanning.
- **Binôme (pair programming)** : Claude écrit le code, et Cédric relit et valide chaque PR. Une PR doit donc rester courte et lisible, avec une description qui dit quoi, pourquoi et comment c'est testé.
- **Client sur place** : les questions de besoin vont à Cédric, qui représente la Communion. Ne devine pas une règle métier absente des specs : pose la question.
- **Jeu de planification** : les user stories et les tâches de `docs/features/` sont la planification. Le cycle est hebdomadaire et suit le rétroplanning de l'index. Les priorités sont décidées par le client, les estimations par l'équipe.
- **Propriété collective du code et standards de code** : tout le code suit les mêmes conventions (specs techniques §4 et §14 : PHP-CS-Fixer, PHPStan max, deptrac, Biome). N'importe quelle partie peut être améliorée par n'importe qui.
- **Rythme soutenable** : on ne sacrifie ni les tests ni la qualité pour tenir une date. On réduit plutôt le périmètre (ordre de report dans l'index), et on le dit tôt.

### Le TDD est imposé par Probity

[Probity](https://github.com/nizos/probity) (`@nizos/probity`, dépendance de dev à la racine) est branché en hook `PreToolUse` dans `.claude/settings.json`. Il vérifie chaque écriture dans `api/src`, `api/tests`, `front/src` et `front/e2e` (configuration : `probity.config.mjs`). Toute écriture de code de production est **refusée** si l'historique récent de la session ne montre pas un test qui échoue et que ce code fait passer.

- Un nouveau test à la fois : écris-le, **exécute-le et montre-le rouge**, puis écris le minimum de code pour le faire passer.
- Refactore seulement quand tout est vert, sans changer le comportement.
- En cas de refus, lis la raison et suis le cycle. Ne contourne jamais le hook : pas d'écriture par `sed`, `cat >` ou d'autres commandes shell, et pas de désactivation.
- Les fichiers générés (`openapi.json`, `schema.d.ts`, `routeTree.gen.ts`) sont exclus de la règle. Les migrations Doctrine (`api/migrations/`) et la configuration sont hors de son périmètre.
- Prérequis : Node.js ≥ 22 et `npm install` à la racine.

## Conventions

- **Langues** : documentation et interface en français, code en anglais. Les termes métier `Priant` et `Inscription` sont gardés en français.
- **Commandes** : passer par le `Makefile` (`make up`, `make test`, `make qa`, `make openapi`…).
- **Commits** : petits et atomiques. Le message dit le pourquoi et cite l'identifiant de tâche (ex. `F09-T03 : VO DisplayName`).

---

## Skills à charger pour ce projet

### Pour Vibe CLI (Mistral)
Le skill `php-coding-standards` est **automatiquement chargé** depuis `./.vibe/skills/php-coding-standards/SKILL.md` quand tu travailles dans ce projet. Il définit :
- Standards de codage PHP (use statements, naming, visibility)
- **TDD obligatoire** (enforcé par Probity)
- **Tests d'acceptance Behat Gherkin** : toute feature backend DOIT avoir un test `.feature` pour valider le comportement extérieur

### Pour Claude CLI (Anthropic)
Claude CLI utilise un système de skills différent (format non compatible avec Vibe). Pour appliquer les mêmes règles :
1. **Créer un skill Claude** équivalent dans `~/.claude/skills/php-coding-standards/` (format YAML/JSON)
2. **Ou copier les règles** de `./.vibe/skills/php-coding-standards/SKILL.md` dans ta configuration Claude

Les règles clés à reproduire :
- ❌ Pas de FQCN (`\App\...`), utiliser des `use` statements
- ✅ TDD obligatoire (test rouge → code vert → refactor)
- ✅ Tests d'acceptance en Gherkin avec Behat pour toute feature backend
