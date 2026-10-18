// Historique de session au format de transcription de Claude Code, que Probity sait lire.
//
// Vibe ne fournit pas de fichier exploitable (transcript_path désigne un dossier au format interne),
// donc on tient le nôtre : une ligne tool_use puis une ligne tool_result par appel d'outil.
import { appendFileSync, mkdirSync } from 'node:fs'
import { dirname, join } from 'node:path'

export function historyFile(dir, sessionId) {
  return join(dir, `${sessionId.replaceAll(/[^A-Za-z0-9_-]/g, '_')}.jsonl`)
}

export function recordToolCall(file, { id, tool, input, output }) {
  mkdirSync(dirname(file), { recursive: true })

  const entries = [
    { type: 'assistant', message: { content: [{ type: 'tool_use', id, name: tool, input }] } },
    { type: 'user', message: { content: [{ type: 'tool_result', tool_use_id: id, content: output }] } },
  ]
  appendFileSync(file, entries.map((entry) => `${JSON.stringify(entry)}\n`).join(''))
}
