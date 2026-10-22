import { Button, Input, Label, TextField } from "@heroui/react";
import { type FormEvent, useState } from "react";
import { baseUrl } from "../api/client";
import { PageTitle } from "../components/PageTitle";

const GENERIC_ERROR_MESSAGE = "Une erreur est survenue. Veuillez réessayer.";
const CONFIRMATION_MESSAGE =
  "Un lien de connexion vient de vous être envoyé à cette adresse.";

type SubmissionState = "idle" | "submitting" | "sent" | "error";

/**
 * / (F08-T07, F07-T01): landing page for the 24-hour prayer chain.
 * Presents the event and offers a single-field registration form: submitting
 * an email creates the account (if needed) and sends a personal login link.
 * The API always answers 202 with the same message, whether the account was
 * created or already existed: this page never leaks account existence.
 */
export function LandingPage() {
  const [state, setState] = useState<SubmissionState>("idle");
  const [message, setMessage] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const formData = new FormData(event.currentTarget);
    const email = String(formData.get("email") ?? "");
    // Honeypot field: legitimate users never see it, bots fill it (F02-T15)
    const website = String(formData.get("website") ?? "");

    setState("submitting");

    try {
      const response = await fetch(`${baseUrl}/auth/register`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify({ email, website }),
      });

      if (!response.ok) {
        throw new Error(`Unexpected status ${response.status}`);
      }

      const body = (await response.json()) as { message?: string };
      setMessage(body.message ?? CONFIRMATION_MESSAGE);
      setState("sent");
    } catch {
      setMessage(GENERIC_ERROR_MESSAGE);
      setState("error");
    }
  }

  return (
    <div className="mx-auto w-full max-w-3xl px-4 py-8">
      <PageTitle>24 heures de prière</PageTitle>
      <p className="mb-6 text-lg">
        Rejoignez la chaîne de prière ininterrompue de 24 heures de la Communion
        de prière Notre-Dame du Mont-Carmel. Chaque priant s'engage sur un
        créneau de 30 minutes.
      </p>
      <p className="mb-8">
        Inscrivez-vous avec votre adresse e-mail : un lien de connexion
        personnel vous sera envoyé, sans mot de passe.
      </p>

      {state === "sent" || state === "error" ? (
        <output>{message}</output>
      ) : (
        <form
          onSubmit={handleSubmit}
          className="flex flex-col items-start gap-6"
        >
          <TextField className="max-w-md">
            <Label>Adresse e-mail</Label>
            <Input name="email" type="email" required autoComplete="email" />
          </TextField>
          {/* Honeypot: hidden from humans, filled by bots */}
          <input
            type="text"
            name="website"
            tabIndex={-1}
            autoComplete="off"
            aria-hidden="true"
            hidden
          />
          <Button
            type="submit"
            variant="primary"
            isDisabled={state === "submitting"}
          >
            {state === "submitting" ? "Envoi en cours…" : "S'inscrire"}
          </Button>
        </form>
      )}
    </div>
  );
}
