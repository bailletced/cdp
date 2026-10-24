import { Link } from "@heroui/react";

/**
 * « Lire le texte » (F12-T05, US-12.1): opens the reading page of one of the
 * viewer's slots. The hidden end of the label names the slot, so a list of
 * these links stays distinct for a screen reader; the visible words start
 * the accessible name (WCAG 2.5.3).
 */
export function ReadTextLink({
  slotId,
  label,
}: {
  slotId: string;
  label: string;
}) {
  return (
    <Link href={`/textes/${slotId}`} className="link font-bold text-link">
      Lire le texte
      <span className="sr-only"> du créneau de {label}</span>
    </Link>
  );
}
