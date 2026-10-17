// Logique du hook Vibe → Probity. Le script exécutable est `../hook.mjs`.
//
// pre_tool  : traduit l'appel vers le vocabulaire de Claude Code, fait évaluer l'action par Probity
//             (qui lit l'historique de session) et renvoie un deny Vibe en cas de refus.
// post_tool : ajoute l'appel terminé à l'historique de session.
import { spawn } from 'node:child_process'
import { existsSync, mkdirSync, writeFileSync } from 'node:fs'
import { dirname } from 'node:path'
import { historyFile, recordToolCall } from './history.mjs'
import { toVibeResponse } from './response.mjs'
import { toClaudeToolCall } from './translate.mjs'

// Seuls ces outils peuvent écrire du code ou lancer des commandes : ce sont les seuls que Probity évalue.
const GUARDED_TOOLS = new Set(['Write', 'Edit', 'Bash'])

const PROBITY_BIN = new URL('./bin.js', import.meta.resolve('@nizos/probity'))

export async function handleHook(payload, { historyDir, configPath, runProbity = runProbityProcess }) {
  const history = historyFile(historyDir, payload.session_id)
  const call = toClaudeToolCall(payload)

  if (payload.hook_event_name === 'post_tool') {
    recordToolCall(history, { id: payload.tool_call_id, ...call, output: payload.tool_output_text ?? payload.tool_error ?? '' })
    return { stdout: '' }
  }

  if (payload.hook_event_name !== 'pre_tool' || !GUARDED_TOOLS.has(call.tool)) return { stdout: '' }

  try {
    ensureExists(history)
    const claudePayload = {
      hook_event_name: 'PreToolUse',
      session_id: payload.session_id,
      cwd: payload.cwd,
      transcript_path: history,
      tool_name: call.tool,
      tool_input: call.input,
    }
    const verdict = toVibeResponse(await runProbity(JSON.stringify(claudePayload), { cwd: payload.cwd, configPath }))

    if (!verdict.allowed) recordToolCall(history, { id: payload.tool_call_id, ...call, output: `Refusé : ${JSON.parse(verdict.stdout).reason}` })

    return { stdout: verdict.stdout }
  } catch (error) {
    // Fail-closed : si l'évaluation n'a pas pu avoir lieu, on refuse.
    return { stdout: JSON.stringify({ decision: 'deny', reason: `Probity (adaptateur Vibe) : ${error.message}` }) }
  }
}

function ensureExists(file) {
  if (existsSync(file)) return
  mkdirSync(dirname(file), { recursive: true })
  writeFileSync(file, '')
}

function runProbityProcess(stdin, { cwd, configPath }) {
  return new Promise((resolve, reject) => {
    const child = spawn(process.execPath, [PROBITY_BIN.pathname, '--agent', 'claude-code', '--config', configPath], { cwd })
    let stdout = ''
    let stderr = ''
    child.stdout.on('data', (chunk) => (stdout += chunk))
    child.stderr.on('data', (chunk) => (stderr += chunk))
    child.on('error', reject)
    child.on('close', (code) => (code === 0 ? resolve(stdout) : reject(new Error(`Probity a échoué (code ${code}) : ${stderr.trim().slice(0, 500)}`))))
    child.stdin.end(stdin)
  })
}
