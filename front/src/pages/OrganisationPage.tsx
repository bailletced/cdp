import { PageTitle } from "../components/PageTitle";

/**
 * /organisation (F04-T04): back-office landing page. The organizers
 * management screen lands here in F04-T05.
 */
export function OrganisationPage() {
  return (
    <div className="mx-auto w-full max-w-3xl">
      <PageTitle>Organisateurs</PageTitle>
      <p>Gestion des organisateurs de la chaîne de prière.</p>
    </div>
  );
}
