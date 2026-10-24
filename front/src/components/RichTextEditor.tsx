import Blockquote from "@tiptap/extension-blockquote";
import Bold from "@tiptap/extension-bold";
import Document from "@tiptap/extension-document";
import HardBreak from "@tiptap/extension-hard-break";
import Italic from "@tiptap/extension-italic";
import Paragraph from "@tiptap/extension-paragraph";
import Text from "@tiptap/extension-text";
import { UndoRedo } from "@tiptap/extensions";
import {
  type Editor,
  EditorContent,
  useEditor,
  useEditorState,
} from "@tiptap/react";
import {
  type KeyboardEvent,
  useEffect,
  useId,
  useLayoutEffect,
  useRef,
  useState,
} from "react";

interface RichTextEditorProps {
  label: string;
  /** HTML limited to p, br, strong, em and blockquote ("" when empty) */
  value: string;
  onChange: (html: string) => void;
  description?: string;
  errorMessage?: string;
  isDisabled?: boolean;
}

// The editor only knows the elements the server keeps (HtmlSanitizer, F06-T01)
const EXTENSIONS = [
  Document,
  Paragraph,
  Text,
  Bold,
  Italic,
  Blockquote,
  HardBreak,
  UndoRedo,
];

interface ToolbarAction {
  label: string;
  run: (editor: Editor) => void;
  /** Toggles carry aria-pressed; history actions do not */
  isActive?: (editor: Editor) => boolean;
}

const ACTIONS: ToolbarAction[] = [
  {
    label: "Gras",
    run: (editor) => editor.chain().focus().toggleBold().run(),
    isActive: (editor) => editor.isActive("bold"),
  },
  {
    label: "Italique",
    run: (editor) => editor.chain().focus().toggleItalic().run(),
    isActive: (editor) => editor.isActive("italic"),
  },
  {
    label: "Citation",
    run: (editor) => editor.chain().focus().toggleBlockquote().run(),
    isActive: (editor) => editor.isActive("blockquote"),
  },
  {
    label: "Annuler",
    run: (editor) => editor.chain().focus().undo().run(),
  },
  {
    label: "Rétablir",
    run: (editor) => editor.chain().focus().redo().run(),
  },
];

function htmlOf(editor: Editor): string {
  return editor.isEmpty ? "" : editor.getHTML();
}

/**
 * Rich text editor (F06-T10, T27): Tiptap 3 limited to paragraphs, bold,
 * italic, quotes and line breaks (Shift + Entrée, for the psalms). The
 * toolbar follows the ARIA toolbar pattern: one tab stop, arrow keys between
 * the buttons, each toggle announcing its state with aria-pressed.
 *
 * The HTML sent back is cleaned again by the server: the editor is a
 * convenience, never a safeguard.
 */
export function RichTextEditor({
  label,
  value,
  onChange,
  description,
  errorMessage,
  isDisabled = false,
}: RichTextEditorProps) {
  const id = useId();
  const labelId = `${id}-label`;
  const descriptionId = `${id}-description`;
  const errorId = `${id}-error`;
  const describedBy =
    [description ? descriptionId : null, errorMessage ? errorId : null]
      .filter(Boolean)
      .join(" ") || undefined;

  // Latest callback without recreating the editor on each render
  const onChangeRef = useRef(onChange);
  onChangeRef.current = onChange;

  const editor = useEditor({
    extensions: EXTENSIONS,
    content: value,
    editable: !isDisabled,
    onUpdate: ({ editor: current }) => onChangeRef.current(htmlOf(current)),
  });

  const pressed = useEditorState({
    editor,
    selector: ({ editor: current }) =>
      ACTIONS.map((action) =>
        current && action.isActive ? action.isActive(current) : null,
      ),
  });

  const [focusedIndex, setFocusedIndex] = useState(0);
  const buttonsRef = useRef<(HTMLButtonElement | null)[]>([]);

  // A value set from outside (form pre-fill) replaces the content without
  // being reported as a user edit. A layout effect, not a passive one: a
  // passive effect could run after the next keystroke and put back the
  // previous value, swallowing what was just typed.
  useLayoutEffect(() => {
    if (editor && value !== htmlOf(editor)) {
      editor.commands.setContent(value, { emitUpdate: false });
    }
  }, [editor, value]);

  useEffect(() => {
    editor?.setEditable(!isDisabled, false);
  }, [editor, isDisabled]);

  // The ARIA attributes live on the contenteditable element itself
  useEffect(() => {
    editor?.setOptions({
      editorProps: {
        attributes: {
          role: "textbox",
          "aria-multiline": "true",
          "aria-labelledby": labelId,
          ...(describedBy ? { "aria-describedby": describedBy } : {}),
          ...(errorMessage ? { "aria-invalid": "true" } : {}),
          class:
            "rich-text min-h-48 rounded-lg border border-border bg-field-background px-4 py-3 focus:outline-none",
        },
      },
    });
  }, [editor, labelId, describedBy, errorMessage]);

  function handleToolbarKeyDown(event: KeyboardEvent<HTMLDivElement>) {
    const last = ACTIONS.length - 1;
    const next =
      event.key === "ArrowRight"
        ? focusedIndex === last
          ? 0
          : focusedIndex + 1
        : event.key === "ArrowLeft"
          ? focusedIndex === 0
            ? last
            : focusedIndex - 1
          : event.key === "Home"
            ? 0
            : event.key === "End"
              ? last
              : null;

    if (null === next) {
      return;
    }

    event.preventDefault();
    setFocusedIndex(next);
    buttonsRef.current[next]?.focus();
  }

  return (
    <div className="flex w-full flex-col gap-2">
      {/* Not a <label>: a contenteditable is no labelable element */}
      <span id={labelId} className="font-medium">
        {label}
      </span>
      <div
        role="toolbar"
        aria-label="Mise en forme"
        aria-controls={id}
        className="flex flex-wrap gap-2"
        onKeyDown={handleToolbarKeyDown}
      >
        {ACTIONS.map((action, index) => (
          <button
            key={action.label}
            ref={(element) => {
              buttonsRef.current[index] = element;
            }}
            type="button"
            tabIndex={index === focusedIndex ? 0 : -1}
            aria-pressed={
              null === (pressed?.[index] ?? null)
                ? undefined
                : Boolean(pressed?.[index])
            }
            disabled={isDisabled || !editor}
            onClick={() => {
              setFocusedIndex(index);
              if (editor) {
                action.run(editor);
              }
            }}
            // A mouse click keeps the focus and the selection in the
            // editor: the next keystrokes go to the text, not the button
            onMouseDown={(event) => event.preventDefault()}
            onFocus={() => setFocusedIndex(index)}
            className="min-h-11 min-w-11 rounded-lg border border-border px-3 aria-pressed:border-accent aria-pressed:bg-accent-soft"
          >
            {action.label}
          </button>
        ))}
      </div>
      <EditorContent editor={editor} id={id} />
      {description ? (
        <p id={descriptionId} className="text-muted">
          {description}
        </p>
      ) : null}
      {errorMessage ? (
        <p id={errorId} className="text-danger">
          {errorMessage}
        </p>
      ) : null}
    </div>
  );
}
