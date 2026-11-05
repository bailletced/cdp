import { useQueryClient } from "@tanstack/react-query";
import { Link, useLocation, useNavigate } from "@tanstack/react-router";
import { useEffect, useRef, useState } from "react";
import { baseUrl } from "../api/client";
import { meQueryOptions } from "../api/hooks";
import { LoadingState } from "../components/LoadingState";
import { PageTitle } from "../components/PageTitle";

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
  const queryClient = useQueryClient();
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
        // The identity just changed: drop the cached "me" query before
        // navigating. Otherwise the /my-space guard can inherit the visitor
        // 401 cached (or still in flight) from when the SPA loaded (F02-T20).
        queryClient.removeQueries({ queryKey: meQueryOptions.queryKey });
        navigate({ to: safeTarget(body.target), replace: true });
      } catch {
        setState("invalid");
      }
    }

    submitLoginLink();
  }, [user, expires, hash, navigate, queryClient]);

  if (state === "invalid") {
    return (
      <div className="mx-auto w-full max-w-3xl px-4 py-8">
        <PageTitle>Connexion</PageTitle>
        <p>
          Ce lien n'est plus valide : il a peut-être expiré ou a déjà été
          utilisé.
        </p>
        <Link to="/receive-my-link">Recevoir un nouveau lien</Link>
      </div>
    );
  }

  return (
    <div className="mx-auto w-full max-w-3xl px-4 py-8">
      <PageTitle>Connexion</PageTitle>
      <LoadingState label="Connexion en cours…" />
    </div>
  );
}
