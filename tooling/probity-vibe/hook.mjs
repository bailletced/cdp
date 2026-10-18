#!/usr/bin/env node
// Hook Mistral Vibe → Probity. Branché sur pre_tool ET post_tool (voir hooks.toml.example).
//
// Entrée : le payload JSON de Vibe sur stdin.
// Sortie : rien pour laisser passer, ou {"decision":"deny","reason":"…"} pour refuser. Code 0.
//
// Variables d'environnement :
//   MISTRAL_API_KEY            clé du validateur IA (voir probity.vibe.config.mjs)
//   PROBITY_VIBE_CONFIG        config Probity à appliquer (défaut : probity.vibe.config.mjs à la racine)
//   PROBITY_VIBE_HISTORY_DIR   où garder l'historique des sessions (défaut : <tmp>/probity-vibe)
import { readFileSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { handleHook } from './lib/hook.mjs'

let payload
try {
  payload = JSON.parse(readFileSync(0, 'utf8'))
} catch (error) {
  // Pas de décision possible sans payload : on sort en erreur, `strict = true` côté Vibe transforme cela en refus.
  process.stderr.write(`probity-vibe : payload JSON illisible (${error.message})\n`)
  process.exit(1)
}

const { stdout } = await handleHook(payload, {
  historyDir: process.env.PROBITY_VIBE_HISTORY_DIR ?? join(tmpdir(), 'probity-vibe'),
  configPath: process.env.PROBITY_VIBE_CONFIG ?? fileURLToPath(new URL('../../probity.vibe.config.mjs', import.meta.url)),
})

process.stdout.write(stdout)
