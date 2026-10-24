import { Button } from "@heroui/react";
import { Link, useParams } from "@tanstack/react-router";
import { useState } from "react";
import { getApiErrorDetail } from "../api/errors";
import {
  useEdition,
  useScheduleEdition,
  useUnscheduleEdition,
} from "../api/hooks";
import { ConfirmDialog } from "../components/ConfirmDialog";
import { ErrorState } from "../components/ErrorState";
import { LoadingState } from "../components/LoadingState";
import { PageTitle } from "../components/PageTitle";
import {
  editionStatusLabel,
  formatParisDateTime,
  groupSlotsByPartOfDay,
  parseEditionSlots,
} from "../lib/editions";

/**
 * /organisation/editions/$editionId (F05-T21): edition detail — status label,
 * dates in Paris time, presentation, the slots grouped by part of day and the
 * lifecycle actions: « Programmer » (draft → scheduled) and « Repasser en
 * brouillon » (scheduled → draft, with a confirmation). The API computes the
 * hybrid status (RG-ED-1); the actions follow the lifecycle.
 */
export function EditionDetailPage() {
  const { editionId } = useParams({
    from: "/organisation/editions/$editionId",
  });
  const { data: edition, isLoading, isError, refetch } = useEdition(editionId);
  const scheduleEdition = useScheduleEdition();
  const unscheduleEdition = useUnscheduleEdition();
  const [confirmingUnschedule, setConfirmingUnschedule] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);

  const isPending = scheduleEdition.isPending || unscheduleEdition.isPending;

  async function handleSchedule() {
    setActionError(null);

    try {
      await scheduleEdition.mutateAsync(editionId);
    } catch (error) {
      setActionError(
        getApiErrorDetail(error) ??
          "L'édition n'a pas pu être programmée. Veuillez réessayer.",
      );
    }
  }

  async function handleUnscheduleConfirm() {
    setConfirmingUnschedule(false);
    setActionError(null);

    try {
      await unscheduleEdition.mutateAsync(editionId);
    } catch (error) {
      setActionError(
        getApiErrorDetail(error) ??
          "L'édition n'a pas pu repasser en brouillon. Veuillez réessayer.",
      );
    }
  }

  if (isLoading) {
    return (
      <div className="mx-auto w-full max-w-3xl">
        <PageTitle>Édition</PageTitle>
        <LoadingState />
      </div>
    );
  }

  if (isError || !edition) {
    return (
      <div className="mx-auto w-full max-w-3xl">
        <PageTitle>Édition</PageTitle>
        <ErrorState
          message="L'édition n'a pas pu être chargée."
          onRetry={() => void refetch()}
        />
      </div>
    );
  }

  const slots = parseEditionSlots(edition.slots);
  const slotGroups = groupSlotsByPartOfDay(slots);
  const isScheduled = "scheduled" === edition.lifecycle;

  return (
    <div className="mx-auto w-full max-w-3xl">
      <PageTitle>{edition.title ?? "Édition"}</PageTitle>
      <div className="flex flex-col items-start gap-6 py-6">
        <div className="flex flex-wrap items-center gap-4">
          <span className="rounded-full bg-surface px-3 py-1">
            {editionStatusLabel(edition.status)}
          </span>
          <Link
            to="/organisation/editions/$editionId/edit"
            params={{ editionId }}
            className="text-link underline underline-offset-4"
          >
            Modifier
          </Link>
          <Link
            to="/organisation/editions/$editionId/texts"
            params={{ editionId }}
            className="text-link underline underline-offset-4"
          >
            Attribuer les textes
          </Link>
        </div>
        <div>
          <p className="text-muted">
            Début : {formatParisDateTime(edition.startsAt)} (heure de Paris)
          </p>
          <p className="text-muted">
            Ouverture des inscriptions : {formatParisDateTime(edition.opensAt)}{" "}
            (heure de Paris)
          </p>
          {edition.scheduledAt ? (
            <p className="text-muted">
              Programmée le {formatParisDateTime(edition.scheduledAt)} (heure de
              Paris)
            </p>
          ) : null}
        </div>
        {edition.presentation ? (
          <section>
            <h2>Présentation</h2>
            <p>{edition.presentation}</p>
          </section>
        ) : null}
        <div className="flex flex-wrap items-center gap-4">
          {isScheduled ? (
            <Button
              variant="outline"
              onClick={() => setConfirmingUnschedule(true)}
              isDisabled={isPending}
            >
              {unscheduleEdition.isPending
                ? "Retour en brouillon…"
                : "Repasser en brouillon"}
            </Button>
          ) : (
            <Button
              variant="primary"
              onClick={() => void handleSchedule()}
              isDisabled={isPending}
            >
              {scheduleEdition.isPending ? "Programmation…" : "Programmer"}
            </Button>
          )}
          <Link
            to="/organisation/editions"
            className="text-link underline underline-offset-4"
          >
            Retour à la liste des éditions
          </Link>
        </div>
        {actionError ? (
          <p role="alert" className="text-danger">
            {actionError}
          </p>
        ) : null}
        <section>
          <h2>Créneaux</h2>
          {slotGroups.length === 0 ? (
            <p className="text-muted">Aucun créneau pour le moment.</p>
          ) : (
            <>
              <p className="text-muted">
                {slots.length} créneaux de 30 minutes.
              </p>
              {slotGroups.map((group, groupIndex) => (
                <section key={`${group.partOfDay}-${groupIndex}`}>
                  <h3>{group.label}</h3>
                  <ul className="flex flex-wrap gap-2">
                    {group.slots.map((slot) => (
                      <li
                        key={slot.rank}
                        className="rounded-lg border border-border px-3 py-1"
                      >
                        {slot.label}
                      </li>
                    ))}
                  </ul>
                </section>
              ))}
            </>
          )}
        </section>
      </div>
      <ConfirmDialog
        isOpen={confirmingUnschedule}
        onClose={() => setConfirmingUnschedule(false)}
        onConfirm={() => void handleUnscheduleConfirm()}
        title="Repasser en brouillon"
        confirmLabel="Repasser en brouillon"
        isDanger
      >
        <p>
          Repasser « {edition.title} » en brouillon ? Elle redeviendra invisible
          du public.
        </p>
      </ConfirmDialog>
    </div>
  );
}
