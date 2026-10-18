import { Link, useLocation, useNavigate } from "@tanstack/react-router";
import { useEffect, useRef, useState } from "react";
import { baseUrl } from "../api/client";

// Post-login targets allowed by the specs (whitelist): never an open redirect
const SAFE_TARGETS = ["/", "/my-space"];

function safeTarget(target: string | undefined): string {
  return target && SAFE_TARGETS.includes(target) ? target : "/my-space";
}

type LoginState = "loading" | "invalid";

/**
 * /login (F02-T18): confirms the personal login link automatically.
 * The email link points to GET /auth/login (API) which redirects here with
 * the link parameters in the query string; this page POSTs them back to
 * /auth/login (form params) exactly once, then redirects to the target.
 */
export function LoginPage() {
  const { search } = useLocation();
  const navigate = useNavigate();
  const [state, setState] = useState<LoginState>("loading");
  // The POST must happen exactly once (StrictMode double-invokes effects)
  const attemptedRef = useRef(false);

  const params = search as Record<string, string | undefined>;
  const user = params.user ?? "";
  const expires = params.expires ?? "";
  const hash = params.hash ?? "";

  useEffect(() => {
    if (attemptedRef.current) {
      return;
    }
    attemptedRef.current = true;

    if (!user || !expires || !hash) {
      setState("invalid");
      return;
    }

    async function submitLoginLink() {
      try {
        const response = await fetch(`${baseUrl}/auth/login`, {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          credentials: "same-origin",
          body: new URLSearchParams({
            user,
            expires,
            hash,
            _target_path: "/my-space",
          }),
        });

        if (!response.ok) {
          // 401: expired, tampered or revoked link (F02-T08)
          setState("invalid");
          return;
        }

        const body = (await response.json()) as { target?: string };
        navigate({ to: safeTarget(body.target), replace: true });
      } catch {
        setState("invalid");
      }
    }

    submitLoginLink();
  }, [user, expires, hash, navigate]);

  if (state === "invalid") {
    return (
      <main>
        <h2>Connexion</h2>
        <p>
          Ce lien n'est plus valide : il a peut-être expiré ou a déjà été
          utilisé.
        </p>
        <Link to="/receive-my-link">Recevoir un nouveau lien</Link>
      </main>
    );
  }

  return (
    <main>
      <h2>Connexion</h2>
      <p>Connexion en cours…</p>
    </main>
  );
}
