/**
 * Read-only rendering of a meditation text (F06-T11): the HTML comes from
 * the API, already cleaned by the server sanitizer (p, br, strong, em,
 * blockquote, no attribute — F06-T01). Same .rich-text typography as the
 * editor, so the preview matches what the priants will read.
 */
export function RichTextView({ html }: { html: string }) {
  return (
    <div
      className="rich-text"
      // biome-ignore lint/security/noDangerouslySetInnerHtml: HTML sanitized server-side (HtmlSanitizer, F06-T01)
      dangerouslySetInnerHTML={{ __html: html }}
    />
  );
}
