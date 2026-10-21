import assert from 'node:assert/strict'
import { test } from 'node:test'
import { mistral } from '../lib/mistral-agent.mjs'

const reply = (content, init = {}) =>
  new Response(JSON.stringify({ choices: [{ message: { content } }] }), { status: 200, headers: { 'content-type': 'application/json' }, ...init })

const agentAnswering = (content) => mistral({ apiKey: 'key', fetchFn: async () => reply(content) })

test('la requête vise Mistral en JSON déterministe avec le prompt de Probity', async () => {
  let call
  const agent = mistral({
    apiKey: 'secret',
    model: 'mistral-large-latest',
    fetchFn: async (url, init) => {
      call = { url, init, body: JSON.parse(init.body) }
      return reply('{"kind":"pass","reason":"ok"}')
    },
  })

  await agent.reason('Juge cette écriture.')

  assert.equal(call.url, 'https://api.mistral.ai/v1/chat/completions')
  assert.equal(call.init.headers.authorization, 'Bearer secret')
  assert.equal(call.body.model, 'mistral-large-latest')
  assert.equal(call.body.temperature, 0)
  assert.deepEqual(call.body.response_format, { type: 'json_object' })
  assert.deepEqual(call.body.messages, [{ role: 'user', content: 'Juge cette écriture.' }])
})

test('un verdict pass est renvoyé tel quel', async () => {
  assert.deepEqual(await agentAnswering('{"kind":"pass","reason":"Test rouge observé."}').reason('p'), {
    kind: 'pass',
    reason: 'Test rouge observé.',
  })
})

test('un verdict violation est renvoyé tel quel', async () => {
  assert.deepEqual(await agentAnswering('{"kind":"violation","reason":"Écris un test."}').reason('p'), {
    kind: 'violation',
    reason: 'Écris un test.',
  })
})

test('un verdict entouré de balises de code est lu', async () => {
  const verdict = await agentAnswering('```json\n{"kind":"pass","reason":"ok"}\n```').reason('p')

  assert.equal(verdict.kind, 'pass')
})

test('un verdict précédé de raisonnement est lu', async () => {
  const verdict = await agentAnswering('Je regarde le test… il échoue bien.\n{"kind":"violation","reason":"Trop de code."}').reason('p')

  assert.deepEqual(verdict, { kind: 'violation', reason: 'Trop de code.' })
})

test("une erreur HTTP est une violation qui cite le statut, pas un passage", async () => {
  const agent = mistral({ apiKey: 'key', fetchFn: async () => new Response('quota', { status: 429 }) })

  const verdict = await agent.reason('p')

  assert.equal(verdict.kind, 'violation')
  assert.match(verdict.reason, /429/)
})

test('un fetch qui échoue est une violation', async () => {
  const agent = mistral({
    apiKey: 'key',
    fetchFn: async () => {
      throw new Error('réseau coupé')
    },
  })

  assert.deepEqual(await agent.reason('p'), { kind: 'violation', reason: 'Validateur Mistral injoignable : réseau coupé' })
})

test("une réponse qui n'est pas un verdict est une violation", async () => {
  const verdict = await agentAnswering('{"decision":"ok"}').reason('p')

  assert.equal(verdict.kind, 'violation')
  assert.match(verdict.reason, /verdict/)
})

test("sans clé d'API, le validateur refuse au lieu de laisser tout passer", async () => {
  const agent = mistral({ apiKey: undefined, fetchFn: async () => assert.fail("aucun appel réseau sans clé") })

  const verdict = await agent.reason('p')

  assert.equal(verdict.kind, 'violation')
  assert.match(verdict.reason, /MISTRAL_API_KEY/)
})

test('un 503 est retenté avant de lire le verdict', async () => {
  let calls = 0
  const agent = mistral({
    apiKey: 'key',
    retryDelaysMs: [1, 1],
    fetchFn: async () => (++calls < 3 ? new Response('indisponible', { status: 503 }) : reply('{"kind":"pass","reason":"ok"}')),
  })

  const verdict = await agent.reason('p')

  assert.deepEqual(verdict, { kind: 'pass', reason: 'ok' })
  assert.equal(calls, 3)
})

test('un 503 persistant épuise les retries et reste une violation', async () => {
  let calls = 0
  const agent = mistral({
    apiKey: 'key',
    retryDelaysMs: [1, 1],
    fetchFn: async () => {
      calls++
      return new Response('indisponible', { status: 503 })
    },
  })

  const verdict = await agent.reason('p')

  assert.equal(verdict.kind, 'violation')
  assert.match(verdict.reason, /503/)
  assert.equal(calls, 3)
})
