/**
 * Full text of a slot (F12-T04, RG-TX-1): the payload of
 * GET /api/slots/{id}/text. The body is the HTML sanitized by the server
 * (F06-T01), the hints are plain text. Null while no text is attributed
 * (RG-TX-2).
 */
export interface SlotTextContent {
  title: string;
  source: string;
  body: string;
  hints: string | null;
}

/** Narrows the text of the payload, typed unknown[] by the OpenAPI schema. */
export function parseSlotTextContent(text: unknown): SlotTextContent | null {
  if (typeof text !== "object" || text === null) {
    return null;
  }

  const candidate = text as Record<string, unknown>;

  if (
    typeof candidate.title !== "string" ||
    typeof candidate.source !== "string" ||
    typeof candidate.body !== "string"
  ) {
    return null;
  }

  return {
    title: candidate.title,
    source: candidate.source,
    body: candidate.body,
    hints: typeof candidate.hints === "string" ? candidate.hints : null,
  };
}
