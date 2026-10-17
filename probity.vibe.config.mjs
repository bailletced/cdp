// Config Probity pour Mistral Vibe : les règles du projet (probity.config.mjs) + un validateur IA Mistral.
//
// Placée à la racine car Probity ancre les globs `files` de chaque bloc sur le dossier du fichier de
// config. Voir tooling/probity-vibe/README.md.
import { execFileSync } from 'node:child_process'
import base from './probity.config.mjs'
import { mistral } from './tooling/probity-vibe/lib/mistral-agent.mjs'

// Résout la clé API comme Vibe : d'abord MISTRAL_API_KEY dans l'environnement,
// sinon le trousseau macOS (service ai.mistral.vibe, compte MISTRAL_API_KEY).
function resolveMistralApiKey() {
  if (process.env.MISTRAL_API_KEY) return process.env.MISTRAL_API_KEY
  try {
    return execFileSync('security', [
      'find-generic-password', '-s', 'ai.mistral.vibe', '-a', 'MISTRAL_API_KEY', '-w',
    ], { encoding: 'utf8', stdio: ['pipe', 'pipe', 'pipe'] }).trim()
  } catch {
    return undefined
  }
}

export default { ...base, ai: mistral({ apiKey: resolveMistralApiKey() }) }
