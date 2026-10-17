import assert from 'node:assert/strict'
import { test } from 'node:test'
import { toVibeResponse } from '../lib/response.mjs'

const probityDeny = (reason) =>
  JSON.stringify({ hookSpecificOutput: { hookEventName: 'PreToolUse', permissionDecision: 'deny', permissionDecisionReason: reason } })

test("une sortie vide de Probity laisse passer l'action", () => {
  assert.deepEqual(toVibeResponse(''), { allowed: true, stdout: '' })
})

test('un refus de Probity devient un deny Vibe avec la même raison', () => {
  const result = toVibeResponse(probityDeny('Probity: écris un test avant.'))

  assert.equal(result.allowed, false)
  assert.deepEqual(JSON.parse(result.stdout), { decision: 'deny', reason: 'Probity: écris un test avant.' })
})

test('une sortie que Probity ne devrait pas produire est refusée plutôt que laissée passer', () => {
  const result = toVibeResponse('quelque chose d’inattendu')

  assert.equal(result.allowed, false)
  assert.equal(JSON.parse(result.stdout).decision, 'deny')
  assert.match(JSON.parse(result.stdout).reason, /sortie inattendue de Probity/)
})
