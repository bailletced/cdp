import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { toClaudeToolCall } from '../lib/translate.mjs'

const fixture = (name) => JSON.parse(readFileSync(new URL(`./fixtures/${name}.json`, import.meta.url), 'utf8'))

test('write_file devient Write', () => {
  assert.deepEqual(toClaudeToolCall(fixture('pre-write-file')), {
    tool: 'Write',
    input: { file_path: '/work/project/hello.php', content: '<?php\necho " Hello ";\n' },
  })
})

test('search_replace devient Edit', () => {
  assert.deepEqual(toClaudeToolCall(fixture('pre-search-replace')), {
    tool: 'Edit',
    input: {
      file_path: '/work/project/hello.php',
      old_string: 'echo " Hello ";',
      new_string: 'echo " Bonjour ";',
      replace_all: false,
    },
  })
})

test('bash devient Bash', () => {
  assert.deepEqual(toClaudeToolCall(fixture('pre-bash')), {
    tool: 'Bash',
    input: { command: 'php -l /work/project/hello.php' },
  })
})

test("l'ancien nom edit, sans préfixe, devient aussi Edit", () => {
  const call = toClaudeToolCall({ tool_name: 'edit', tool_input: { file_path: '/a.php', old_string: 'a', new_string: 'b' } })

  assert.deepEqual(call, { tool: 'Edit', input: { file_path: '/a.php', old_string: 'a', new_string: 'b', replace_all: false } })
})

test('un outil inconnu garde son nom, sans préfixe, et ses arguments', () => {
  assert.deepEqual(toClaudeToolCall({ tool_name: 'file_system.grep', tool_input: { pattern: 'x' } }), {
    tool: 'grep',
    input: { pattern: 'x' },
  })
})

test('read_file devient Read', () => {
  assert.deepEqual(toClaudeToolCall(fixture('pre-read-file')), { tool: 'Read', input: { file_path: '/work/project/hello.php' } })
})
