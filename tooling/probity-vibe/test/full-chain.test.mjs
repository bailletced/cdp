// Chaîne complète : payload Vibe → hook → Probity (vraie config du dépôt + validateur Mistral) → faux serveur Mistral.
import assert from 'node:assert/strict'
import { spawn } from 'node:child_process'
import { mkdtempSync } from 'node:fs'
import { createServer } from 'node:http'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { after, before, test } from 'node:test'
import { fileURLToPath } from 'node:url'

const repoRoot = fileURLToPath(new URL('../../../', import.meta.url))
const script = fileURLToPath(new URL('../hook.mjs', import.meta.url))

let server
let endpoint
let verdict
let requests

before(async () => {
  server = createServer((request, response) => {
    requests.push(request.headers.authorization)
    request.resume().on('end', () => {
      response.setHeader('content-type', 'application/json')
      response.end(JSON.stringify({ choices: [{ message: { content: JSON.stringify(verdict) } }] }))
    })
  })
  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve))
  endpoint = `http://127.0.0.1:${server.address().port}/v1/chat/completions`
})

after(() => server.close())

function run(filePath) {
  requests = []
  const payload = {
    session_id: 'chain',
    cwd: repoRoot,
    hook_event_name: 'pre_tool',
    tool_name: 'file_system.write_file',
    tool_call_id: 'c1',
    tool_input: { file_path: filePath, content: '<?php final class Foo {}' },
  }
  const env = {
    ...process.env,
    MISTRAL_API_KEY: 'test-key',
    PROBITY_MISTRAL_ENDPOINT: endpoint,
    PROBITY_VIBE_HISTORY_DIR: mkdtempSync(join(tmpdir(), 'probity-vibe-chain-')),
  }

  return new Promise((resolve) => {
    const child = spawn(process.execPath, [script], { env })
    let stdout = ''
    child.stdout.on('data', (chunk) => (stdout += chunk))
    child.on('close', (status) => resolve({ status, stdout }))
    child.stdin.end(JSON.stringify(payload))
  })
}

test("une écriture dans api/src que le validateur juge sans test préalable est refusée avec son motif", async () => {
  verdict = { kind: 'violation', reason: 'Écris d’abord un test qui échoue.' }

  const result = await run(join(repoRoot, 'api/src/Chain/Foo.php'))

  assert.equal(result.status, 0)
  assert.equal(JSON.parse(result.stdout).decision, 'deny')
  assert.match(JSON.parse(result.stdout).reason, /Écris d’abord un test qui échoue/)
  assert.deepEqual(requests, ['Bearer test-key'])
})

test("une écriture dans api/src que le validateur accepte est laissée passer", async () => {
  verdict = { kind: 'pass', reason: 'Test rouge observé.' }

  const result = await run(join(repoRoot, 'api/src/Chain/Foo.php'))

  assert.equal(result.stdout, '')
  assert.equal(requests.length, 1)
})

test("une écriture hors des dossiers gardés ne consulte pas le validateur", async () => {
  verdict = { kind: 'violation', reason: 'ne doit pas être appelé' }

  const result = await run(join(repoRoot, 'docs/notes.md'))

  assert.equal(result.stdout, '')
  assert.equal(requests.length, 0)
})
