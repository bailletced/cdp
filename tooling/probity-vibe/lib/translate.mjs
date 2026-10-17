// Traduit un appel d'outil Mistral Vibe vers le vocabulaire de Claude Code, que Probity comprend.
//
// Vibe nomme ses outils `file_system.write_file`, `file_system.search_replace`, etc. (2.26.1) ;
// des versions plus récentes parlent d'`edit`. On retire donc le préfixe et on accepte les deux noms.

const stripNamespace = (name) => name.split('.').at(-1)

const TRANSLATIONS = {
  write_file: (input) => ({ tool: 'Write', input: { file_path: input.file_path, content: input.content } }),
  search_replace: editToClaude,
  edit: editToClaude,
  bash: (input) => ({ tool: 'Bash', input: { command: input.command } }),
  read_file: (input) => ({ tool: 'Read', input: { file_path: input.file_path } }),
}

function editToClaude(input) {
  return {
    tool: 'Edit',
    input: {
      file_path: input.file_path,
      old_string: input.old_string,
      new_string: input.new_string,
      replace_all: input.replace_all ?? false,
    },
  }
}

export function toClaudeToolCall(payload) {
  const name = stripNamespace(payload.tool_name)
  const translate = TRANSLATIONS[name]

  return translate ? translate(payload.tool_input ?? {}) : { tool: name, input: payload.tool_input ?? {} }
}
