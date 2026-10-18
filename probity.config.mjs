// Probity : impose le TDD (rouge → vert → refactor) aux agents de code.
// Voir agent.md, section « Méthode : Extreme Programming ».
// Le TDD ne s'applique qu'au back-end (api/) ; le front (front/) n'est pas concerné.
import { defineConfig, enforceTdd } from '@nizos/probity'

const projectAddendum = `
Contexte du projet :
- Back-end PHP (Symfony, API Platform, architecture hexagonale) : tests PHPUnit dans api/tests/, code dans api/src/.
- Un test qui échoue doit avoir été observé (exécution de make test ou phpunit dans l'historique récent) avant tout code de production qui le fait passer.
- Un test NEUF s'écrit avant d'avoir pu être exécuté : il est donc normal qu'il n'ait jamais été vu échouer au moment de son écriture, et qu'il cite une classe ou un comportement qui n'existe pas encore. Ne refuse pas l'écriture d'un test neuf pour cette raison ; exige seulement qu'il soit un seul nouveau test, puis qu'il soit exécuté et vu rouge AVANT d'écrire le code de production. Un « No tests executed » ou une exécution antérieure à l'écriture du fichier n'est pas une preuve contre ce test.
- Un test de caractérisation d'un comportement déjà en place (configuration, défaut d'un framework) peut passer d'emblée.
- Le refactoring n'est autorisé que lorsque les tests sont au vert, sans changement de comportement.
`

export default defineConfig({
  rules: [
    {
      files: [
        'api/src/**',
        'api/tests/**',
      ],
      rules: [
        enforceTdd({
          instructions: (defaults) => `${defaults}\n${projectAddendum}`,
          maxEvents: 20,
        }),
      ],
    },
  ],
})
