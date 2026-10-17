// Probity : impose le TDD (rouge → vert → refactor) aux agents de code.
// Voir CLAUDE.md, section « Méthode : Extreme Programming ».
import { defineConfig, enforceTdd } from '@nizos/probity'

const projectAddendum = `
Contexte du projet :
- Back-end PHP (Symfony, API Platform, architecture hexagonale) : tests PHPUnit dans api/tests/, code dans api/src/.
- Front-end React/TypeScript : tests Vitest (*.test.ts, *.test.tsx) à côté du code dans front/src/, e2e Playwright dans front/e2e/.
- Un test qui échoue doit avoir été observé (exécution de make test, phpunit ou vitest dans l'historique récent) avant tout code de production qui le fait passer.
- Le refactoring n'est autorisé que lorsque les tests sont au vert, sans changement de comportement.
`

export default defineConfig({
  rules: [
    {
      files: [
        'api/src/**',
        'api/tests/**',
        'front/src/**',
        'front/e2e/**',
        // Fichiers générés : jamais écrits à la main.
        '!front/src/api/openapi.json',
        '!front/src/api/schema.d.ts',
        '!front/src/routeTree.gen.ts',
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
