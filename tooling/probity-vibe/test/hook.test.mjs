import assert from 'node:assert/strict'
import { mkdtempSync, readFileSync, writeFileSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { test } from 'node:test'
import { fileURLToPath } from 'node:url'
import { handleHook } from '../lib/hook.mjs'
import { historyFile } from '../lib/history.mjs'

const configPath = fileURLToPath(new URL('./fixtures/probity.test.config.mjs', import.meta.url))

function setup() {
  const project = mkdtempSync(join(tmpdir(), 'probity-vibe-project-'))
  const historyDir = mkdtempSync(join(tmpdir(), 'probity-vibe-history-'))
  const options = { historyDir, configPath }
  const base = { session_id: 's1', transcript_path: '/ignored', cwd: project, parent_session_id: null }

  return { project, historyDir, options, base, history: () => readFileSync(historyFile(historyDir, 's1'), 'utf8') }
}

const pre = (base, toolName, toolInput) => ({
  ...base,
  hook_event_name: 'pre_tool',
  tool_name: `file_system.${toolName}`,
  tool_call_id: 'c1',
  tool_input: toolInput,
})

test('une écriture que les règles acceptent est laissée passer', async () => {
  const { project, options, base } = setup()

  const result = await handleHook(pre(base, 'write_file', { file_path: join(project, 'a.php'), content: '<?php echo 1;' }), options)

  assert.equal(result.stdout, '')
})

test('une écriture qu’une règle refuse est refusée avec la raison de Probity', async () => {
  const { project, options, base } = setup()

  const result = await handleHook(pre(base, 'write_file', { file_path: join(project, 'a.php'), content: '<?php // FORBIDDEN' }), options)

  const response = JSON.parse(result.stdout)
  assert.equal(response.decision, 'deny')
  assert.match(response.reason, /contenu interdit/)
})

test('une édition search_replace est évaluée sur le contenu du fichier après modification', async () => {
  const { project, options, base } = setup()
  const file = join(project, 'hello.php')
  writeFileSync(file, '<?php echo "Hello";\n')

  const result = await handleHook(pre(base, 'search_replace', { file_path: file, old_string: 'Hello', new_string: 'FORBIDDEN', replace_all: false }), options)

  assert.equal(JSON.parse(result.stdout).decision, 'deny')
})

test('une commande bash refusée par une règle est refusée', async () => {
  const { options, base } = setup()

  const result = await handleHook(pre(base, 'bash', { command: 'rm -rf /tmp/x' }), options)

  assert.match(JSON.parse(result.stdout).reason, /commande destructive interdite/)
})

test("un outil que les règles ne gardent pas n'appelle même pas Probity", async () => {
  const { options, base } = setup()

  const result = await handleHook(pre(base, 'read_file', { file_path: '/x' }), { ...options, configPath: '/inexistant.mjs' })

  assert.equal(result.stdout, '')
})

test('une config Probity introuvable refuse l’action plutôt que de la laisser passer', async () => {
  const { project, options, base } = setup()

  const result = await handleHook(pre(base, 'write_file', { file_path: join(project, 'a.php'), content: 'x' }), { ...options, configPath: '/inexistant.mjs' })

  const response = JSON.parse(result.stdout)
  assert.equal(response.decision, 'deny')
  assert.match(response.reason, /Cannot find module/)
})

test('si le lancement de Probity plante, l’action est refusée avec la cause', async () => {
  const { project, options, base } = setup()
  const runProbity = async () => {
    throw new Error('spawn impossible')
  }

  const result = await handleHook(pre(base, 'write_file', { file_path: join(project, 'a.php'), content: 'x' }), { ...options, runProbity })

  const response = JSON.parse(result.stdout)
  assert.equal(response.decision, 'deny')
  assert.match(response.reason, /adaptateur Vibe.*spawn impossible/)
})

test("un appel terminé est ajouté à l'historique avec son résultat", async () => {
  const { options, base, history } = setup()

  await handleHook(
    {
      ...base,
      hook_event_name: 'post_tool',
      tool_name: 'file_system.bash',
      tool_call_id: 'c9',
      tool_input: { command: 'php -l a.php' },
      tool_status: 'success',
      tool_output_text: 'returncode: 0',
    },
    options,
  )

  assert.match(history(), /"name":"Bash"/)
  assert.match(history(), /"content":"returncode: 0"/)
})

test("un appel refusé figure dans l'historique avec la raison du refus", async () => {
  const { project, options, base, history } = setup()

  await handleHook(pre(base, 'write_file', { file_path: join(project, 'a.php'), content: 'FORBIDDEN' }), options)

  assert.match(history(), /"name":"Write"/)
  assert.match(history(), /contenu interdit/)
})
