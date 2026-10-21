// Validateur IA de Probity (`Config.ai`) adossé à l'API de Mistral.
//
// Probity lui passe un prompt de revue TDD et attend un verdict {kind: "pass" | "violation", reason}.
// Tout ce qui ne ressemble pas à un verdict clair devient une violation (fail-closed) :
// une panne du validateur ne doit jamais laisser passer du code non relu.
// Les 503 (API indisponible) sont retentés automatiquement avant de conclure à une violation.

const ENDPOINT = 'https://api.mistral.ai/v1/chat/completions'
const DEFAULT_MODEL = 'mistral-large-latest'
const DEFAULT_TIMEOUT_MS = 45_000

// Un 503 est une indisponibilité transitoire de l'API : on retente avant de refuser.
const RETRYABLE_STATUS = new Set([503])
const DEFAULT_RETRY_DELAYS_MS = [500, 1000]

const violation = (reason) => ({ kind: 'violation', reason })

export function mistral({
  apiKey = process.env.MISTRAL_API_KEY,
  model = process.env.PROBITY_MISTRAL_MODEL ?? DEFAULT_MODEL,
  endpoint = process.env.PROBITY_MISTRAL_ENDPOINT ?? ENDPOINT,
  timeoutMs = DEFAULT_TIMEOUT_MS,
  retryDelaysMs = DEFAULT_RETRY_DELAYS_MS,
  fetchFn = fetch,
} = {}) {
  return {
    async reason(prompt) {
      if (!apiKey) return violation("Validateur Mistral indisponible : la variable d'environnement MISTRAL_API_KEY n'est pas définie.")

      let text
      try {
        let response
        for (let attempt = 0; ; attempt++) {
          response = await fetchFn(endpoint, {
            method: 'POST',
            headers: { authorization: `Bearer ${apiKey}`, 'content-type': 'application/json' },
            body: JSON.stringify({
              model,
              temperature: 0,
              response_format: { type: 'json_object' },
              messages: [{ role: 'user', content: prompt }],
            }),
            signal: AbortSignal.timeout(timeoutMs),
          })

          if (response.ok || !RETRYABLE_STATUS.has(response.status) || attempt >= retryDelaysMs.length) break

          await new Promise((resolve) => setTimeout(resolve, retryDelaysMs[attempt]))
        }

        if (!response.ok) return violation(`Validateur Mistral en erreur : HTTP ${response.status}.`)

        text = (await response.json()).choices?.[0]?.message?.content
      } catch (error) {
        return violation(`Validateur Mistral injoignable : ${error.message}`)
      }

      return parseVerdict(text)
    },
  }
}

function parseVerdict(text) {
  const parsed = typeof text === 'string' ? extractJson(text) : undefined

  if (parsed && ['pass', 'violation'].includes(parsed.kind) && typeof parsed.reason === 'string') {
    return { kind: parsed.kind, reason: parsed.reason }
  }

  return violation(`Le validateur Mistral n'a pas renvoyé de verdict exploitable : ${String(text).slice(0, 500)}`)
}

// Le modèle peut entourer son JSON de balises de code ou le faire précéder d'un raisonnement.
function extractJson(text) {
  return tryParse(text.trim()) ?? tryParse(stripFence(text)) ?? lastBalancedObject(text)
}

function tryParse(text) {
  try {
    return JSON.parse(text)
  } catch {
    return undefined
  }
}

function stripFence(text) {
  return text
    .replace(/^```(?:json)?\s*/, '')
    .replace(/\s*```$/, '')
    .trim()
}

function lastBalancedObject(text) {
  const starts = [...text.matchAll(/\{/g)].map((match) => match.index).reverse()

  for (const start of starts) {
    let depth = 0
    let inString = false
    let escaped = false

    for (let i = start; i < text.length; i++) {
      const char = text[i]
      if (escaped) escaped = false
      else if (inString) {
        if (char === '\\') escaped = true
        else if (char === '"') inString = false
      } else if (char === '"') inString = true
      else if (char === '{') depth++
      else if (char === '}' && --depth === 0) {
        const parsed = tryParse(text.slice(start, i + 1))
        if (parsed !== undefined) return parsed
        break
      }
    }
  }

  return undefined
}
