import { Button } from "@heroui/react";
import { useNavigate } from "@tanstack/react-router";
import { useRef, useState } from "react";
import { getApiErrorDetail } from "../api/errors";
import { useRegisterInscriptions } from "../api/hooks";
import {
  dismissSuggestion,
  lastInscription,
  parseSlotSuggestion,
  rememberInscription,
} from "../lib/lastInscription";
import { withLocalTime } from "../lib/localTime";

/**
 * Suggestion after an inscription (F13-T07, US-13.3): the empty slot the API
 * found next to the last one taken. « Le prendre aussi » registers on it in
 * one click with the information kept in memory, then offers the next
 * suggestion, if any; « Non merci » dismisses it. Without that information
 * (a reload), nothing is offered.
 *
 * Both actions remove the buttons: the focus moves to the area that holds
 * the outcome, announced politely.
 */
export function SuggestionOffer({
  registeredIds,
}: { registeredIds: string[] }) {
  const navigate = useNavigate();
  const registerInscriptions = useRegisterInscriptions();
  const [suggestion, setSuggestion] = useState(
    () => lastInscription()?.suggestion ?? null,
  );
  const [outcome, setOutcome] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const areaRef = useRef<HTMLDivElement>(null);

  async function takeIt() {
    const last = lastInscription();

    if (!suggestion || !last) {
      return;
    }

    setError(null);

    try {
      const inscription = await registerInscriptions.mutateAsync({
        slotIds: [suggestion.slotId],
        displayName: last.displayName,
        email: last.email,
        consent: last.consent,
        website: "",
      });
      const next = parseSlotSuggestion(inscription?.suggestion);
      rememberInscription({ ...last, suggestion: next });
      setOutcome(`Vous êtes aussi inscrit sur ${suggestion.label}.`);
      setSuggestion(next);
      navigate({
        to: "/inscription/confirmation",
        search: { creneaux: [...registeredIds, suggestion.slotId].join(",") },
        replace: true,
      });
    } catch (apiError) {
      setError(
        getApiErrorDetail(apiError) ??
          "L'inscription sur ce créneau n'a pas pu être enregistrée.",
      );
      setSuggestion(null);
    }

    areaRef.current?.focus();
  }

  function decline() {
    dismissSuggestion();
    setSuggestion(null);
    areaRef.current?.focus();
  }

  return (
    <div ref={areaRef} tabIndex={-1} className="mb-8 outline-none">
      <output className={outcome ? "mb-4 block text-lg" : "sr-only"}>
        {outcome}
      </output>
      {error ? (
        <p role="alert" className="mb-4 text-danger">
          {error}
        </p>
      ) : null}
      {suggestion && !registeredIds.includes(suggestion.slotId) ? (
        <section
          aria-labelledby="suggestion-title"
          className="rounded-2xl border border-accent bg-accent-soft p-4 sm:p-5"
        >
          <h2 id="suggestion-title" className="mb-3 text-xl font-semibold">
            Un créneau voisin est encore vide
          </h2>
          <p className="font-display text-2xl font-bold lining-nums tabular-nums">
            {withLocalTime(suggestion.label, suggestion.startsAt)}
          </p>
          {suggestion.textTitle ? (
            <p className="mt-1">{suggestion.textTitle}</p>
          ) : null}
          <div className="mt-4 flex flex-wrap gap-3">
            {/* Not isPending: the offer unmounts once taken, and React Aria
                would announce the pending state by reference to a button
                that no longer exists */}
            <Button
              variant="primary"
              onClick={() => void takeIt()}
              isDisabled={registerInscriptions.isPending}
            >
              {registerInscriptions.isPending
                ? "Inscription en cours…"
                : "Le prendre aussi"}
            </Button>
            <Button variant="outline" onClick={decline}>
              Non merci
            </Button>
          </div>
        </section>
      ) : null}
    </div>
  );
}
