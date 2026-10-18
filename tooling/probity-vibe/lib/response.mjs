// Traduit la réponse de Probity (format Claude Code) vers celle qu'attend un hook pre_tool de Vibe.
//
// Probity : sortie vide = pas d'objection ; refus = hookSpecificOutput.permissionDecision "deny".
// Vibe    : sortie vide = passage ; refus = {"decision":"deny","reason":"…"} avec le code de sortie 0.

const deny = (reason) => ({ allowed: false, stdout: JSON.stringify({ decision: 'deny', reason }) })

export function toVibeResponse(probityStdout) {
  if (probityStdout.trim() === '') return { allowed: true, stdout: '' }

  try {
    const output = JSON.parse(probityStdout).hookSpecificOutput
    if (output?.permissionDecision === 'deny') return deny(output.permissionDecisionReason)
  } catch {
    // Sortie non JSON : traitée ci-dessous comme inattendue.
  }

  // Fail-closed : mieux vaut bloquer une action légitime que laisser passer une violation non évaluée.
  return deny(`Probity (adaptateur Vibe) : sortie inattendue de Probity : ${probityStdout.slice(0, 500)}`)
}
