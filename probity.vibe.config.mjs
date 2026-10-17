// Config Probity pour Mistral Vibe : les règles du projet (probity.config.mjs) + un validateur IA Mistral.
//
// Placée à la racine car Probity ancre les globs `files` de chaque bloc sur le dossier du fichier de
// config. Voir tooling/probity-vibe/README.md.
import base from './probity.config.mjs'
import { mistral } from './tooling/probity-vibe/lib/mistral-agent.mjs'

export default { ...base, ai: mistral() }
