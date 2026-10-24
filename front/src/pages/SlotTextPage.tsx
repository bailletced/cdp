import { useQuery } from "@tanstack/react-query";
import { Link, useParams } from "@tanstack/react-router";
import { slotTextQueryOptions } from "../api/hooks";
import { ErrorState } from "../components/ErrorState";
import { LoadingState } from "../components/LoadingState";
import { PageTitle } from "../components/PageTitle";
import { RichTextView } from "../components/RichTextView";
import { withLocalTime } from "../lib/localTime";
import { parseSlotTextContent } from "../lib/slotText";

const LINK_CLASS = "text-link underline underline-offset-4";

/** « samedi 28 novembre » → « Samedi 28 novembre » */
function capitalized(value: string): string {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

/**
 * /textes/$slotId (F12-T04, US-12.1, US-12.2): the reading page of a slot —
 * its day and schedule (with the local time outside Europe/Paris), the
 * title, the source, the body in large type and the meditation hints.
 *
 * The API answers 404 to anyone but the priants of the slot and the
 * organizers (RG-TX-1): the page then explains the text is reserved, with a
 * link to register on this slot and one to receive the personal link (a
 * priant on another device).
 */
export function SlotTextPage() {
  const { slotId } = useParams({ from: "/textes/$slotId" });
  const query = useQuery(slotTextQueryOptions(slotId));

  if (query.isPending) {
    return (
      <div className="mx-auto w-full max-w-3xl px-4 py-8">
        <PageTitle>Texte du créneau</PageTitle>
        <LoadingState label="Chargement du texte…" />
      </div>
    );
  }

  if (query.isError) {
    return (
      <div className="mx-auto w-full max-w-3xl px-4 py-8">
        <PageTitle>Texte du créneau</PageTitle>
        <ErrorState
          message="Le texte n'a pas pu être chargé."
          onRetry={() => query.refetch()}
        />
      </div>
    );
  }

  const slot = query.data;

  if (slot === null) {
    return (
      <div className="mx-auto w-full max-w-3xl px-4 py-8">
        <PageTitle>Texte réservé</PageTitle>
        <p className="mt-4 mb-6 text-lg">
          Le texte complet est réservé aux priants inscrits sur ce créneau.
        </p>
        <ul className="grid gap-3">
          <li>
            <Link
              to="/inscription"
              search={{ creneaux: slotId }}
              className={LINK_CLASS}
            >
              M'inscrire sur ce créneau
            </Link>
          </li>
          <li>
            Déjà inscrit depuis un autre appareil ?{" "}
            <Link to="/receive-my-link" className={LINK_CLASS}>
              Recevoir mon lien personnel
            </Link>
          </li>
        </ul>
      </div>
    );
  }

  const text = parseSlotTextContent(slot.text);
  const schedule = `${capitalized(slot.dayLabel ?? "")}, ${withLocalTime(
    slot.label ?? "",
    slot.startsAt ?? "",
  )}`;

  return (
    <article className="mx-auto w-full max-w-3xl px-4 py-8">
      <p className="mb-2 text-muted">{schedule}</p>
      <PageTitle>{text?.title ?? "Le texte de votre créneau"}</PageTitle>
      {text ? (
        <>
          <p className="mt-2 mb-8 text-lg italic">{text.source}</p>
          <RichTextView html={text.body} />
          {text.hints ? (
            <section
              aria-labelledby="pistes"
              className="mt-10 max-w-[65ch] border-accent border-l-2 pl-5"
            >
              <h2 id="pistes" className="mb-3 text-xl font-semibold">
                Pistes de méditation
              </h2>
              <p className="whitespace-pre-line text-lg leading-relaxed">
                {text.hints}
              </p>
            </section>
          ) : null}
        </>
      ) : (
        <p className="mt-4 text-lg">
          Le texte de ce créneau sera bientôt disponible.
        </p>
      )}
      <p className="mt-12">
        <Link to="/" className={LINK_CLASS}>
          Retour au planning
        </Link>
      </p>
    </article>
  );
}
