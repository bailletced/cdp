import { useQueryClient } from "@tanstack/react-query";
import { useNavigate } from "@tanstack/react-router";
import { useState } from "react";
import { baseUrl } from "../api/client";
import { useViewer } from "../api/hooks";

/**
 * /my-space (F02-T19): personal space skeleton.
 * The route guard redirects visitors to /receive-my-link; this page only
 * renders for an authenticated viewer.
 */
export function MySpacePage() {
  const { viewer, isLoading } = useViewer();
  const queryClient = useQueryClient();
  const navigate = useNavigate();
  const [loggingOut, setLoggingOut] = useState(false);

  async function handleLogout() {
    setLoggingOut(true);
    try {
      await fetch(`${baseUrl}/auth/logout`, {
        method: "POST",
        credentials: "same-origin",
      });
    } finally {
      // Drop the cached identity: the next GET /api/me answers 401 (visitor)
      queryClient.removeQueries({ queryKey: ["me"] });
      navigate({ to: "/receive-my-link", replace: true });
    }
  }

  if (isLoading || !viewer) {
    return (
      <main>
        <h2>Mon espace</h2>
        <p>Chargement…</p>
      </main>
    );
  }

  return (
    <main>
      <h2>Mon espace</h2>
      <p>{viewer.email}</p>
      <button type="button" onClick={handleLogout} disabled={loggingOut}>
        {loggingOut ? "Déconnexion en cours…" : "Me déconnecter"}
      </button>
    </main>
  );
}
