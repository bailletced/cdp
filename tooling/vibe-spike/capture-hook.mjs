#!/usr/bin/env node
// Spike : enregistre les payloads que Mistral Vibe envoie à ses hooks (pre_tool, post_tool, post_agent).
//
// Passage pur : rien sur stdout, code de sortie 0, donc Vibe ne bloque ni ne modifie jamais une action.
// Les captures vont dans ./captures/ (ignoré par git) : payload complet, état de `transcript_path`
// et, si c'est un fichier raisonnable, une copie pour analyser son format.
import { appendFileSync, copyFileSync, existsSync, mkdirSync, readdirSync, readFileSync, statSync, writeFileSync } from 'node:fs'
import { basename, dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const root = dirname(fileURLToPath(import.meta.url))
const captures = join(root, 'captures')
const MAX_COPY_BYTES = 8 * 1024 * 1024

function describePath(path) {
  if (typeof path !== 'string' || path === '') return { given: path ?? null }
  if (!existsSync(path)) return { given: path, exists: false }
  const stat = statSync(path)
  const info = { given: path, exists: true, isFile: stat.isFile(), isDirectory: stat.isDirectory(), bytes: stat.size }
  if (stat.isDirectory()) info.entries = readdirSync(path).slice(0, 30)
  if (stat.isFile()) {
    const lines = readFileSync(path, 'utf8').split('\n').filter(Boolean)
    info.lines = lines.length
    info.firstLineKeys = safeKeys(lines[0])
    info.lastLineKeys = safeKeys(lines.at(-1))
  }
  return info
}

function safeKeys(line) {
  try {
    return Object.keys(JSON.parse(line))
  } catch {
    return 'not-json'
  }
}

function main() {
  const raw = readFileSync(0, 'utf8')
  mkdirSync(captures, { recursive: true })

  let payload
  try {
    payload = JSON.parse(raw)
  } catch (error) {
    appendFileSync(join(captures, 'errors.log'), `${new Date().toISOString()} invalid JSON: ${error.message}\n${raw.slice(0, 500)}\n`)
    return
  }

  const stamp = new Date().toISOString().replaceAll(':', '-')
  const label = [payload.hook_event_name ?? 'unknown', payload.tool_name ?? ''].filter(Boolean).join('-')
  const base = `${stamp}-${label}`

  writeFileSync(join(captures, `${base}.json`), `${JSON.stringify(payload, null, 2)}\n`)

  const transcript = describePath(payload.transcript_path)
  appendFileSync(join(captures, 'transcript-path.jsonl'), `${JSON.stringify({ at: stamp, event: payload.hook_event_name, ...transcript })}\n`)

  if (transcript.isFile && transcript.bytes <= MAX_COPY_BYTES) {
    mkdirSync(join(captures, 'transcripts'), { recursive: true })
    copyFileSync(payload.transcript_path, join(captures, 'transcripts', `${base}-${basename(payload.transcript_path)}`))
  }
}

try {
  main()
} catch (error) {
  process.stderr.write(`capture-hook: ${error.message}\n`)
}
process.exit(0)
