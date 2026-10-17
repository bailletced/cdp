import assert from 'node:assert/strict'
import { spawnSync } from 'node:child_process'
import { mkdtempSync, readFileSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { test } from 'node:test'
import { fileURLToPath } from 'node:url'

const script = fileURLToPath(new URL('../hook.mjs', import.meta.url))
const configPath = fileURLToPath(new URL('./fixtures/probity.test.config.mjs', import.meta.url))
const fixture = (name) => JSON.parse(readFileSync(new URL(`./fixtures/${name}.json`, import.meta.url), 'utf8'))

function run(payload) {
  const cwd = mkdtempSync(join(tmpdir(), 'probity-vibe-cli-'))
  const env = { ...process.env, PROBITY_VIBE_CONFIG: configPath, PROBITY_VIBE_HISTORY_DIR: mkdtempSync(join(tmpdir(), 'probity-vibe-hist-')) }
  const input = typeof payload === 'string' ? payload : JSON.stringify({ ...payload, cwd })

  return spawnSync(process.execPath, [script], { input, env, encoding: 'utf8' })
}

test('un payload pre_tool refusé par une règle sort un deny sur stdout, code 0', () => {
  const payload = fixture('pre-write-file')
  payload.tool_input.content = '<?php // FORBIDDEN'

  const result = run(payload)

  assert.equal(result.status, 0)
  assert.equal(JSON.parse(result.stdout).decision, 'deny')
})

test('un payload pre_tool accepté sort une réponse vide, code 0', () => {
  const result = run(fixture('pre-write-file'))

  assert.equal(result.status, 0)
  assert.equal(result.stdout, '')
})

test('un payload post_tool sort une réponse vide, code 0', () => {
  const result = run(fixture('post-write-file'))

  assert.equal(result.status, 0)
  assert.equal(result.stdout, '')
})

test("un payload illisible sort en erreur, pour que Vibe (strict) refuse l'action", () => {
  const result = run('pas du json')

  assert.notEqual(result.status, 0)
  assert.match(result.stderr, /JSON/)
})
