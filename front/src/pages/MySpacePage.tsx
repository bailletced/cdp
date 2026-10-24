import { useQueryClient } from "@tanstack/react-query";
import { useNavigate } from "@tanstack/react-router";
import { useState } from "react";
import { baseUrl } from "../api/client";
import { useViewer } from "../api/hooks";
import { CurrentEditionPlanning } from "../components/CurrentEditionPlanning";
import { LoadingState } from "../components/LoadingState";
import { PageTitle } from "../components/PageTitle";

/**
 * /my-space (F02-T19, F08): personal space — slot selection for the prayer chain.
 * After logging in via a personal link, the priant lands here to pick their
 * 30-minute slots in the planning of the current edition. The route guard
 * redirects visitors to the landing page; this page only renders for an
 * authenticated viewer, who is sent here from / as well.
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
      navigate({ to: "/", replace: true });
    }
  }

  if (isLoading || !viewer) {
    return (
      <div className="mx-auto w-full max-w-3xl px-4 py-8">
        <PageTitle>Mon espace</PageTitle>
        <LoadingState />
      </div>
    );
  }

  return (
    <div className="mx-auto w-full max-w-3xl px-4 py-8">
      <PageTitle>Mon espace</PageTitle>
      <p className="mb-6">{viewer.email}</p>
      <h2 className="mb-4 text-xl font-semibold">Choisissez vos créneaux</h2>
      <p className="mb-8 text-lg">
        Sélectionnez un ou plusieurs créneaux de 30 minutes pour participer à la
        chaîne de prière de 24 heures.
      </p>
      <CurrentEditionPlanning />
      <button
        type="button"
        onClick={handleLogout}
        disabled={loggingOut}
        className="mt-8"
      >
        {loggingOut ? "Déconnexion en cours…" : "Me déconnecter"}
      </button>
    </div>
  );
}
