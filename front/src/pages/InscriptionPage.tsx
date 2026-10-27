import { Button, Checkbox } from "@heroui/react";
import { useField, useForm } from "@tanstack/react-form";
import { useQuery } from "@tanstack/react-query";
import { Link, useNavigate, useSearch } from "@tanstack/react-router";
import { useRef, useState } from "react";
import {
  type ApiError,
  getApiErrorDetail,
  getApiErrorStatus,
} from "../api/errors";
import {
  editionSlotsQueryOptions,
  useCurrentEdition,
  useMe,
  useRegisterInscriptions,
} from "../api/hooks";
import { useAltchaCheck } from "../components/AltchaCheck";
import { EmptyState } from "../components/EmptyState";
import { ErrorState } from "../components/ErrorState";
import { FormField, applyApiErrors } from "../components/FormField";
import { LoadingState } from "../components/LoadingState";
import { PageTitle } from "../components/PageTitle";
import {
  inscriptionFormSchema,
  parseCreneauxParam,
  selectSlotsByIds,
} from "../lib/inscription";
import {
  parseSlotSuggestion,
  rememberInscription,
} from "../lib/lastInscription";
import { withLocalTime } from "../lib/localTime";
import { parsePlanningSlots, slotTemporalState } from "../lib/planning";
import type { PlanningSlot } from "../lib/planning";
import { useNow } from "../lib/serverClock";
import { standardSchemaValidator } from "../lib/standardSchema";

const LINK_CLASS = "text-link underline underline-offset-4";

/**
 * /inscription?creneaux=a,b,c (F09-T15 to T18, US-09.1): inscription form
 * for the slots selected on the planning grid (F08-T10).
 *
 * The selected slots are recap'd with their label and a « Retirer » action
 * (the URL parameter is validated: missing, empty or unknown ids are
 * signalled, US-09.5). Past slots and the slots the viewer already holds
 * (RG-IN-3) are flagged and never submitted. The
 * form collects the display name, the email and the consent (RG-IN-5,
 * RG-IN-7): in session, the name and email are pre-filled and the consent
 * checkbox is hidden once the consent has been given.
 */
export function InscriptionPage() {
  const { creneaux } = useSearch({ from: "/inscription" });
  const ids = parseCreneauxParam(creneaux);
  const editionQuery = useCurrentEdition();
  const meQuery = useMe();
  const nowMs = useNow();
  const navigate = useNavigate();

  const edition = editionQuery.data ?? null;
  const slotsQuery = useQuery({
    ...editionSlotsQueryOptions(edition?.id ?? ""),
    enabled: Boolean(edition?.id),
  });

  if (meQuery.isPending || editionQuery.isPending) {
    return (
      <div className="mx-auto w-full max-w-3xl px-4 py-8">
        <PageTitle>Inscription</PageTitle>
        <LoadingState label="Chargement…" />
      </div>
    );
  }

  if (editionQuery.isError) {
    return (
      <div className="mx-auto w-full max-w-3xl px-4 py-8">
        <PageTitle>Inscription</PageTitle>
        <ErrorState
          message="L'édition n'a pas pu être chargée."
          onRetry={() => void editionQuery.refetch()}
        />
      </div>
    );
  }

  // US-09.5: no open edition → the inscriptions are closed
  if (null === edition || !edition.id) {
    return (
      <div className="mx-auto w-full max-w-3xl px-4 py-8">
        <PageTitle>Inscription</PageTitle>
        <EmptyState message="Les inscriptions ne sont pas ouvertes pour le moment." />
        <Link to="/" className={LINK_CLASS}>
          Retour à l&apos;accueil
        </Link>
      </div>
    );
  }

  if (slotsQuery.isPending) {
    return (
      <div className="mx-auto w-full max-w-3xl px-4 py-8">
        <PageTitle>Inscription</PageTitle>
        <LoadingState label="Chargement des créneaux…" />
      </div>
    );
  }

  if (slotsQuery.isError) {
    return (
      <div className="mx-auto w-full max-w-3xl px-4 py-8">
        <PageTitle>Inscription</PageTitle>
        <ErrorState
          message="Les créneaux n'ont pas pu être chargés."
          onRetry={() => void slotsQuery.refetch()}
        />
      </div>
    );
  }

  const slots = parsePlanningSlots(slotsQuery.data?.slots);
  const selected = selectSlotsByIds(slots, ids);

  // The parameter is validated: no slot selected at all
  if (ids.length === 0) {
    return (
      <div className="mx-auto w-full max-w-3xl px-4 py-8">
        <PageTitle>Inscription</PageTitle>
        <EmptyState message="Aucun créneau sélectionné." />
        <p className="mb-4">
          Retournez à l&apos;accueil pour choisir un ou plusieurs créneaux.
        </p>
        <Link to="/" className={LINK_CLASS}>
          Retour à l&apos;accueil
        </Link>
      </div>
    );
  }

  // The parameter is validated: none of the selected slots exists
  if (selected.length === 0) {
    return (
      <div className="mx-auto w-full max-w-3xl px-4 py-8">
        <PageTitle>Inscription</PageTitle>
        <EmptyState message="Les créneaux sélectionnés sont introuvables." />
        <Link to="/" className={LINK_CLASS}>
          Retour à l&apos;accueil
        </Link>
      </div>
    );
  }

  // Past slots (US-09.5) and the slots already taken by the viewer (RG-IN-3)
  // are signalled and never submitted
  const notPast = selected.filter(
    (slot) => slotTemporalState(slot, nowMs) !== "past",
  );
  const submittable = notPast.filter((slot) => !slot.mine);
  const consentRequired = !(meQuery.data?.consentGiven ?? false);

  return (
    <div className="mx-auto w-full max-w-3xl px-4 py-8">
      <PageTitle>Inscription</PageTitle>
      <SelectedSlotsRecap
        slots={selected}
        nowMs={nowMs}
        onRemove={(slotId) =>
          navigate({
            to: "/inscription",
            search: {
              creneaux: selected
                .filter((slot) => slot.id !== slotId)
                .map((slot) => slot.id)
                .join(","),
            },
            replace: true,
          })
        }
      />
      {selected.length < ids.length ? (
        <p className="mb-4 text-muted">
          Certains créneaux sélectionnés n&apos;existent plus.
        </p>
      ) : null}
      {notPast.length === 0 ? (
        <p role="alert" className="text-danger">
          Les créneaux sélectionnés sont passés.
        </p>
      ) : submittable.length === 0 ? (
        <p role="alert" className="text-danger">
          Vous êtes déjà inscrit sur les créneaux sélectionnés.
        </p>
      ) : (
        <InscriptionForm
          slotIds={submittable.map((slot) => slot.id)}
          defaultDisplayName={meQuery.data?.displayName ?? ""}
          defaultEmail={meQuery.data?.email ?? ""}
          consentRequired={consentRequired}
        />
      )}
    </div>
  );
}

/**
 * Récapitulatif des créneaux choisis (F09-T15): libellé de chaque créneau,
 * états « créneau passé » et « déjà inscrit » (RG-IN-3) portés par un
 * libellé (jamais par la couleur seule),
 * et action « Retirer » qui met à jour le paramètre d'URL.
 */
function SelectedSlotsRecap({
  slots,
  nowMs,
  onRemove,
}: {
  slots: PlanningSlot[];
  nowMs: number;
  onRemove: (slotId: string) => void;
}) {
  return (
    <section aria-label="Créneaux sélectionnés" className="mb-8">
      <h2 className="mb-4 text-xl font-semibold">Créneaux sélectionnés</h2>
      <ul className="grid gap-2">
        {slots.map((slot) => {
          const isPast = slotTemporalState(slot, nowMs) === "past";
          const isLeftOut = isPast || slot.mine;

          return (
            <li
              key={slot.id}
              className="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-border bg-surface p-3"
            >
              <span className={isLeftOut ? "text-muted" : undefined}>
                {withLocalTime(slot.label, slot.startsAt)}
                {isPast ? (
                  <span className="text-muted"> (créneau passé)</span>
                ) : slot.mine ? (
                  <span className="text-muted"> (déjà inscrit)</span>
                ) : null}
              </span>
              <Button
                type="button"
                variant="outline"
                size="sm"
                aria-label={`Retirer le créneau de ${slot.label}`}
                onClick={() => onRemove(slot.id)}
              >
                Retirer
              </Button>
            </li>
          );
        })}
      </ul>
    </section>
  );
}

/**
 * Formulaire d'inscription (F09-T16/T17/T18): nom affiché, e-mail,
 * consentement, champ piège `website`. La validation est un Standard Schema
 * borné par l'OpenAPI (F09-T16) ; les violations 422 de l'API sont
 * redirigées vers leur champ, le 409 est affiché en message métier (RG-IN-3),
 * le 429 invite à réessayer plus tard (F15) ; une fois la vérification
 * anti-robot activée, sa solution part avec le formulaire (F15-T06).
 */
function InscriptionForm({
  slotIds,
  defaultDisplayName,
  defaultEmail,
  consentRequired,
}: {
  slotIds: string[];
  defaultDisplayName: string;
  defaultEmail: string;
  consentRequired: boolean;
}) {
  const navigate = useNavigate();
  const registerInscriptions = useRegisterInscriptions();
  // Honeypot read from the DOM at submit time: a bot filling the hidden
  // input bypasses React's controlled inputs (§6.7)
  const websiteRef = useRef<HTMLInputElement>(null);
  const [formError, setFormError] = useState<string | null>(null);
  // Anti-robot check, once switched on during an attack (F15-T06)
  const altcha = useAltchaCheck();

  const form = useForm({
    defaultValues: {
      displayName: defaultDisplayName,
      email: defaultEmail,
      consent: false,
      website: "",
    },
    validators: {
      onSubmit: standardSchemaValidator(inscriptionFormSchema(consentRequired)),
    },
    onSubmit: async ({ value }) => {
      setFormError(null);

      try {
        const payload = {
          slotIds,
          displayName: value.displayName.trim(),
          email: value.email.trim(),
          consent: consentRequired ? value.consent : true,
          website: websiteRef.current?.value ?? "",
          ...(altcha.payload ? { altcha: altcha.payload } : {}),
        };
        const inscription = await registerInscriptions.mutateAsync(payload);
        // « Le prendre aussi » re-uses this information (F13-T07)
        rememberInscription({
          displayName: payload.displayName,
          email: payload.email,
          consent: payload.consent,
          suggestion: parseSlotSuggestion(inscription?.suggestion),
        });

        navigate({
          to: "/inscription/confirmation",
          search: { creneaux: slotIds.join(",") },
          replace: true,
        });
      } catch (error) {
        const apiError = error as ApiError;
        const status = getApiErrorStatus(apiError);

        if (status === 422 && apiError.violations) {
          // Each 422 violation lands on its own field (F09-T10)
          applyApiErrors(form, apiError);
          // The slots and the anti-robot check have no field of their own
          const formViolation = apiError.violations.find(
            (violation) =>
              violation.propertyPath === "slotIds" ||
              violation.propertyPath === "altcha",
          );
          if (formViolation) {
            setFormError(formViolation.message);
          }
        } else if (status === 429) {
          // F15: beyond the hourly limit of the inscriptions
          setFormError("Trop de tentatives, réessayez dans quelques minutes.");
        } else if (status === 409) {
          // RG-IN-3: already registered on this slot (double click, race)
          setFormError(
            getApiErrorDetail(apiError) ??
              "Vous êtes déjà inscrit sur ce créneau.",
          );
        } else if (status === 404) {
          setFormError(
            "Ce créneau n'existe plus. Retournez à l'accueil pour en choisir un autre.",
          );
        } else {
          setFormError(
            getApiErrorDetail(apiError) ??
              "L'inscription n'a pas pu être enregistrée. Veuillez réessayer.",
          );
        }
      }
    },
  });

  const consentField = useField({ form, name: "consent" });
  const consentErrors = consentField.state.meta.errors;
  const isPending = registerInscriptions.isPending;

  return (
    <form
      onSubmit={(event) => {
        event.preventDefault();
        void form.handleSubmit();
      }}
      className="flex flex-col items-start gap-6"
    >
      <h2 className="text-xl font-semibold">Vos informations</h2>
      <FormField
        form={form}
        name="displayName"
        label="Nom affiché"
        description="Prénom et nom, ou nom de famille."
        placeholder="Famille Dupont"
        autoComplete="name"
      />
      <FormField
        form={form}
        name="email"
        label="Adresse e-mail"
        type="email"
        autoComplete="email"
      />
      {consentRequired ? (
        <div className="flex flex-col items-start gap-1">
          <Checkbox
            isSelected={consentField.state.value}
            onChange={(isSelected) => consentField.handleChange(isSelected)}
            isInvalid={consentErrors.length > 0}
            isDisabled={isPending}
          >
            <Checkbox.Content>
              <Checkbox.Control>
                <Checkbox.Indicator />
              </Checkbox.Control>
              <span>
                J&apos;accepte la{" "}
                <Link
                  to="/confidentialite"
                  className="text-link underline underline-offset-4"
                >
                  politique de confidentialité
                </Link>
              </span>
            </Checkbox.Content>
          </Checkbox>
          {consentErrors[0] ? (
            <p role="alert" className="text-danger">
              {consentErrors[0]}
            </p>
          ) : null}
        </div>
      ) : null}
      {/* Honeypot: hidden from humans, filled by bots (§6.7) */}
      <input
        ref={websiteRef}
        type="text"
        name="website"
        tabIndex={-1}
        autoComplete="off"
        aria-hidden="true"
        hidden
      />
      {altcha.widget}
      <Button
        type="submit"
        variant="primary"
        isDisabled={isPending || (altcha.enabled && !altcha.payload)}
      >
        {isPending ? "Envoi en cours…" : "S'inscrire"}
      </Button>
      {formError ? (
        <p role="alert" className="text-danger">
          {formError}
        </p>
      ) : null}
    </form>
  );
}
