import { PageTitle } from "../components/PageTitle";

/**
 * /confidentialite — provisional content (F01-T07: the footer links here;
 * the definitive legal content lands with F25).
 */
export function ConfidentialitePage() {
  return (
    <div className="mx-auto grid w-full max-w-3xl gap-4 px-4 py-8">
      <PageTitle>Confidentialité</PageTitle>
      <p>
        Contenu provisoire : la politique de confidentialité définitive sera
        publiée avant l&apos;ouverture des inscriptions (F25).
      </p>
      <p>
        Votre nom et votre adresse e-mail servent uniquement à organiser la
        chaîne de prière, à vous envoyer votre lien personnel et le texte à
        méditer pendant votre créneau.
      </p>
    </div>
  );
}
