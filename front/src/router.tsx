import {
  createRoute,
  createRouter,
  lazyRouteComponent,
  redirect,
} from "@tanstack/react-router";
import { meQueryOptions } from "./api/hooks";
import { queryClient } from "./api/queryClient";
import { Root } from "./components/Root";
import { ConfidentialitePage } from "./pages/ConfidentialitePage";
import { DemoPage } from "./pages/DemoPage";
import { EditionEditPage, EditionFormPage } from "./pages/EditionFormPage";
import { ErrorPage } from "./pages/ErrorPage";
import { InscriptionConfirmationPage } from "./pages/InscriptionConfirmationPage";
import { InscriptionPage } from "./pages/InscriptionPage";
import { LandingPage } from "./pages/LandingPage";
import { LoginPage } from "./pages/LoginPage";
import { MySpacePage } from "./pages/MySpacePage";
import { ReceiveMyLinkPage } from "./pages/ReceiveMyLinkPage";
import { SlotTextPage } from "./pages/SlotTextPage";

// Landing page (F08-T07, F07-T01): public home page with the registration
// form. Reserved to visitors: a signed-in priant goes straight to /my-space.
const landingRoute = createRoute({
  getParentRoute: () => Root,
  path: "/",
  beforeLoad: async ({ context }) => {
    const me = await context.queryClient
      .ensureQueryData(meQueryOptions)
      .catch(() => null);

    if (null !== me) {
      throw redirect({ to: "/my-space" });
    }
  },
  component: LandingPage,
});

// The inscription pages carry the selected slot ids in ?creneaux=… (built
// by the planning grid, F08-T10): the parameter is normalized here into a
// comma-separated string, the pages validate it against the edition's slots.
function inscriptionSearch(search: Record<string, unknown>): {
  creneaux: string;
} {
  const raw = search.creneaux;

  return {
    creneaux: Array.isArray(raw)
      ? raw.filter((id): id is string => typeof id === "string").join(",")
      : typeof raw === "string"
        ? raw
        : "",
  };
}

// Inscription form (F09-T15 to T18)
const inscriptionRoute = createRoute({
  getParentRoute: () => Root,
  path: "/inscription",
  validateSearch: inscriptionSearch,
  component: InscriptionPage,
});

// Inscription confirmation (F09-T19)
const inscriptionConfirmationRoute = createRoute({
  getParentRoute: () => Root,
  path: "/inscription/confirmation",
  validateSearch: inscriptionSearch,
  component: InscriptionConfirmationPage,
});

// Reading page of a slot (F12-T04): reserved by the API to the priants of
// the slot and the organizers, the page explains it to anyone else
const slotTextRoute = createRoute({
  getParentRoute: () => Root,
  path: "/textes/$slotId",
  component: SlotTextPage,
});

const receiveMyLinkRoute = createRoute({
  getParentRoute: () => Root,
  path: "/receive-my-link",
  component: ReceiveMyLinkPage,
});

// Provisional content until F25 (the footer links here, F01-T07)
const confidentialiteRoute = createRoute({
  getParentRoute: () => Root,
  path: "/confidentialite",
  component: ConfidentialitePage,
});

// Component demonstration page (F01-T05)
const demoRoute = createRoute({
  getParentRoute: () => Root,
  path: "/demo",
  component: DemoPage,
});

const loginRoute = createRoute({
  getParentRoute: () => Root,
  path: "/login",
  component: LoginPage,
});

// Route guard (F02-T19): a visitor (401 on /api/me) is redirected
// to the landing page before the page mounts.
const mySpaceRoute = createRoute({
  getParentRoute: () => Root,
  path: "/my-space",
  beforeLoad: async ({ context }) => {
    try {
      await context.queryClient.ensureQueryData(meQueryOptions);
    } catch {
      throw redirect({ to: "/" });
    }
  },
  component: MySpacePage,
});

// Back-office section (F04-T04): lazy-loaded so the back-office bundle is
// never fetched on public pages. Reserved to organizers: visitors (401 on
// /api/me) are redirected to the landing page, authenticated priants to
// /my-space.
const organisationRoute = createRoute({
  getParentRoute: () => Root,
  path: "/organisation",
  beforeLoad: async ({ context }) => {
    const me = await context.queryClient
      .ensureQueryData(meQueryOptions)
      .catch(() => null);

    if (null === me) {
      throw redirect({ to: "/" });
    }

    if (!me.isOrganizer) {
      throw redirect({ to: "/my-space" });
    }
  },
  component: lazyRouteComponent(
    () => import("./components/OrganisationLayout"),
    "OrganisationLayout",
  ),
});

const organisationIndexRoute = createRoute({
  getParentRoute: () => organisationRoute,
  path: "/",
  component: lazyRouteComponent(
    () => import("./pages/OrganisationPage"),
    "OrganisationPage",
  ),
});

// Editions list (F05-T18): lazy-loaded with the back-office section
const organisationEditionsRoute = createRoute({
  getParentRoute: () => organisationRoute,
  path: "/editions",
  component: lazyRouteComponent(
    () => import("./pages/EditionsPage"),
    "EditionsPage",
  ),
});

// Edition creation form (F05-T20): eagerly loaded (not lazy like the rest of
// the back-office) so the form renders on the first paint — its tests query
// the fields synchronously after render.
const organisationEditionNewRoute = createRoute({
  getParentRoute: () => organisationRoute,
  path: "/editions/new",
  component: EditionFormPage,
});

// Edition edit form (F05-T20)
const organisationEditionEditRoute = createRoute({
  getParentRoute: () => organisationRoute,
  path: "/editions/$editionId/edit",
  component: EditionEditPage,
});

// Edition detail (F05-T21): lazy-loaded with the back-office section
const organisationEditionDetailRoute = createRoute({
  getParentRoute: () => organisationRoute,
  path: "/editions/$editionId",
  component: lazyRouteComponent(
    () => import("./pages/EditionDetailPage"),
    "EditionDetailPage",
  ),
});

// Text attribution (F07-T06): lazy-loaded with the back-office section
const organisationEditionTextsRoute = createRoute({
  getParentRoute: () => organisationRoute,
  path: "/editions/$editionId/texts",
  component: lazyRouteComponent(
    () => import("./pages/AttributionPage"),
    "AttributionPage",
  ),
});

// « Répartir dans l'ordre » (F07-T08, F07-T09)
const organisationEditionDistributeRoute = createRoute({
  getParentRoute: () => organisationRoute,
  path: "/editions/$editionId/distribute",
  component: lazyRouteComponent(
    () => import("./pages/DistributePage"),
    "DistributePage",
  ),
});

// Text library (F06-T12): lazy-loaded with the back-office section
const organisationTextsRoute = createRoute({
  getParentRoute: () => organisationRoute,
  path: "/texts",
  component: lazyRouteComponent(() => import("./pages/TextsPage"), "TextsPage"),
});

// Text forms (F06-T13): lazy-loaded with the back-office section (Tiptap
// stays out of the public bundle)
const organisationTextNewRoute = createRoute({
  getParentRoute: () => organisationRoute,
  path: "/texts/new",
  component: lazyRouteComponent(
    () => import("./pages/TextFormPage"),
    "TextFormPage",
  ),
});

const organisationTextEditRoute = createRoute({
  getParentRoute: () => organisationRoute,
  path: "/texts/$textId/edit",
  component: lazyRouteComponent(
    () => import("./pages/TextFormPage"),
    "TextEditPage",
  ),
});

// Define the routes
export const routeTree = Root.addChildren([
  landingRoute,
  receiveMyLinkRoute,
  confidentialiteRoute,
  inscriptionRoute,
  inscriptionConfirmationRoute,
  slotTextRoute,
  demoRoute,
  loginRoute,
  mySpaceRoute,
  organisationRoute.addChildren([
    organisationIndexRoute,
    organisationEditionsRoute,
    organisationEditionNewRoute,
    organisationEditionEditRoute,
    organisationEditionDetailRoute,
    organisationEditionTextsRoute,
    organisationEditionDistributeRoute,
    organisationTextsRoute,
    organisationTextNewRoute,
    organisationTextEditRoute,
  ]),
]);

export const router = createRouter({
  routeTree,
  context: { queryClient },
  // Global error page (F01-T11): as a default error component, it renders
  // inside the public layout when any route fails (a root-route
  // errorComponent would replace the layout).
  defaultErrorComponent: ErrorPage,
});

// Declare the types for TypeScript
declare module "@tanstack/react-router" {
  interface Register {
    router: typeof router;
  }
}
