import assert from 'node:assert/strict'
import { mkdtempSync, readFileSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { test } from 'node:test'
import { historyFile, recordToolCall } from '../lib/history.mjs'

const lines = (file) =>
  readFileSync(file, 'utf8')
    .split('\n')
    .filter(Boolean)
    .map((line) => JSON.parse(line))

test("un appel d'outil est enregistré comme un tool_use suivi de son tool_result", () => {
  const file = historyFile(mkdtempSync(join(tmpdir(), 'probity-vibe-')), 'session-1')

  recordToolCall(file, { id: 'call-1', tool: 'Bash', input: { command: 'php -l a.php' }, output: 'No syntax errors' })

  assert.deepEqual(lines(file), [
    { type: 'assistant', message: { content: [{ type: 'tool_use', id: 'call-1', name: 'Bash', input: { command: 'php -l a.php' } }] } },
    { type: 'user', message: { content: [{ type: 'tool_result', tool_use_id: 'call-1', content: 'No syntax errors' }] } },
  ])
})

test("un identifiant de session ne peut pas sortir du dossier d'historique", () => {
  const file = historyFile('/tmp/history', '../../etc/passwd')

  assert.equal(file, '/tmp/history/______etc_passwd.jsonl')
})
