import { type ReactNode, useEffect, useRef, useState } from "react";
import { baseUrl } from "../api/client";

declare module "react" {
  namespace JSX {
    interface IntrinsicElements {
      "altcha-widget": DetailedHTMLProps<
        HTMLAttributes<HTMLElement>,
        HTMLElement
      > & { auto?: string; challenge?: string; language?: string };
    }
  }
}

/** The anti-robot check of a public form, as the form uses it. */
export interface AltchaCheck {
  /** The check is on: the form waits for its solution. */
  enabled: boolean;
  /** The solution to send along, once the widget found it. */
  payload: string | null;
  /** The widget to show in the form, or nothing while the check is off. */
  widget: ReactNode;
}

/**
 * The ALTCHA anti-robot check of the public forms (F15-T06, US-15.3): off
 * by default — GET /api/altcha-challenge answers 204 and nothing shows. Once
 * switched on during an attack, the widget solves the challenge in the
 * visitor's browser as soon as the form shows (in under a second), and its
 * solution goes with the form.
 */
export function useAltchaCheck(): AltchaCheck {
  const [challenge, setChallenge] = useState<unknown>(null);
  const [payload, setPayload] = useState<string | null>(null);

  useEffect(() => {
    let active = true;

    fetch(`${baseUrl}/api/altcha-challenge`, { credentials: "same-origin" })
      .then(async (response) => {
        if (active && response.status === 200) {
          setChallenge(await response.json());
        }
      })
      // Unreachable: the check stays off here, the API refuses if it was on
      .catch(() => undefined);

    return () => {
      active = false;
    };
  }, []);

  return {
    enabled: challenge !== null,
    payload,
    widget:
      challenge === null ? null : (
        <AltchaWidget challenge={challenge} onChange={setPayload} />
      ),
  };
}

/** The <altcha-widget> element, loaded only once the check is on. */
function AltchaWidget({
  challenge,
  onChange,
}: {
  challenge: unknown;
  onChange: (payload: string | null) => void;
}) {
  const ref = useRef<HTMLElement>(null);

  useEffect(() => {
    void import("altcha");
    void import("altcha/i18n/fr-fr");
  }, []);

  useEffect(() => {
    const widget = ref.current;
    const listener = (event: Event) => {
      const { state, payload } = (
        event as CustomEvent<{ state?: string; payload?: string }>
      ).detail;
      onChange(state === "verified" && payload ? payload : null);
    };

    widget?.addEventListener("statechange", listener);

    return () => widget?.removeEventListener("statechange", listener);
  }, [onChange]);

  return (
    <altcha-widget
      ref={ref}
      challenge={JSON.stringify(challenge)}
      auto="onload"
      language="fr-fr"
    />
  );
}
