import { Button } from "@heroui/react";
import { useNavigate } from "@tanstack/react-router";
import { useState } from "react";
import { getApiErrorDetail } from "../api/errors";
import { useRegisterInscriptions } from "../api/hooks";
import {
  parseSlotSuggestion,
  rememberInscription,
} from "../lib/lastInscription";

/**
 * « M'inscrire (n) » for a priant in session who already consented
 * (RG-IN-5, RG-IN-7): the inscription form would only repeat the name and
 * the e-mail the session knows, so the ticked slots are registered at once,
 * then the confirmation shows — as after the form, suggestion included.
 */
export function QuickInscriptionButton({
  slotIds,
  displayName,
  email,
}: {
  slotIds: string[];
  displayName: string;
  email: string;
}) {
  const navigate = useNavigate();
  const registerInscriptions = useRegisterInscriptions();
  const [error, setError] = useState<string | null>(null);

  async function register() {
    setError(null);

    try {
      const inscription = await registerInscriptions.mutateAsync({
        slotIds,
        displayName,
        email,
        consent: true,
        website: "",
      });
      // « Le prendre aussi » re-uses this information (F13-T07)
      rememberInscription({
        displayName,
        email,
        consent: true,
        suggestion: parseSlotSuggestion(inscription?.suggestion),
      });
      navigate({
        to: "/inscription/confirmation",
        search: { creneaux: slotIds.join(",") },
      });
    } catch (apiError) {
      setError(
        getApiErrorDetail(apiError) ??
          "L'inscription n'a pas pu être enregistrée. Veuillez réessayer.",
      );
    }
  }

  return (
    <div className="flex w-full flex-col gap-2 sm:w-auto sm:items-end">
      {error ? (
        <p role="alert" className="text-danger">
          {error}
        </p>
      ) : null}
      <Button
        variant="primary"
        className="min-h-11 w-full font-bold sm:w-auto"
        isDisabled={slotIds.length === 0 || registerInscriptions.isPending}
        onClick={() => void register()}
      >
        {registerInscriptions.isPending
          ? "Inscription en cours…"
          : `M'inscrire (${slotIds.length})`}
      </Button>
    </div>
  );
}
