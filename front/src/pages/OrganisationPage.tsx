import { Button, Input, Label, TextField } from "@heroui/react";
import { type FormEvent, useState } from "react";
import { getApiErrorDetail } from "../api/errors";
import {
  useAddOrganizer,
  useOrganizers,
  useRemoveOrganizer,
} from "../api/hooks";
import type { components } from "../api/schema";
import { ConfirmDialog } from "../components/ConfirmDialog";
import { EmptyState } from "../components/EmptyState";
import { ErrorState } from "../components/ErrorState";
import { LoadingState } from "../components/LoadingState";
import { PageTitle } from "../components/PageTitle";

type OrganizerView = components["schemas"]["AdminOrganizer.jsonld"];

function formatDate(isoDate: string | null | undefined): string {
  if (!isoDate) {
    return "";
  }

  const date = new Date(isoDate);

  return Number.isNaN(date.getTime())
    ? isoDate
    : date.toLocaleDateString("fr-FR");
}

/**
 * /organisation (F04-T04/T05): back-office landing page — the organizers
 * list (with who added each organizer and when), the add-by-email form and
 * the removal (with confirmation) of an organizer.
 */
export function OrganisationPage() {
  const { data, isLoading, isError, refetch } = useOrganizers();
  const addOrganizer = useAddOrganizer();
  const removeOrganizer = useRemoveOrganizer();
  const [addError, setAddError] = useState<string | null>(null);
  const [removing, setRemoving] = useState<OrganizerView | null>(null);
  const [removeError, setRemoveError] = useState<string | null>(null);

  async function handleAdd(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const formData = new FormData(event.currentTarget);
    const email = String(formData.get("email") ?? "");

    setAddError(null);

    try {
      await addOrganizer.mutateAsync(email);
      event.currentTarget.reset();
    } catch (error) {
      setAddError(
        getApiErrorDetail(error) ??
          "L'organisateur n'a pas pu être ajouté. Veuillez réessayer.",
      );
    }
  }

  async function handleRemoveConfirm() {
    if (!removing?.id) {
      return;
    }

    setRemoveError(null);

    try {
      await removeOrganizer.mutateAsync(removing.id);
      setRemoving(null);
    } catch (error) {
      setRemoving(null);
      // The API refuses removing the last organizer (422): show its reason
      setRemoveError(
        getApiErrorDetail(error) ??
          "L'organisateur n'a pas pu être retiré. Veuillez réessayer.",
      );
    }
  }

  return (
    <div className="mx-auto w-full max-w-3xl">
      <PageTitle>Organisateurs</PageTitle>
      <form
        onSubmit={handleAdd}
        className="flex flex-col items-start gap-4 py-6"
      >
        <TextField className="max-w-md">
          <Label>Adresse e-mail</Label>
          <Input name="email" type="email" required autoComplete="email" />
        </TextField>
        <Button
          type="submit"
          variant="primary"
          isDisabled={addOrganizer.isPending}
        >
          {addOrganizer.isPending ? "Ajout en cours…" : "Ajouter"}
        </Button>
        {addError ? (
          <p role="alert" className="text-danger">
            {addError}
          </p>
        ) : null}
      </form>
      {isLoading ? (
        <LoadingState />
      ) : isError ? (
        <ErrorState
          message="La liste des organisateurs n'a pas pu être chargée."
          onRetry={() => void refetch()}
        />
      ) : !data || data.member.length === 0 ? (
        <EmptyState message="Aucun organisateur pour le moment." />
      ) : (
        <ul className="flex flex-col gap-4">
          {data.member.map((organizer) => (
            <li
              key={organizer.id ?? organizer.email ?? undefined}
              className="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-border px-4 py-3"
            >
              <div>
                <p className="font-medium">{organizer.email}</p>
                <p className="text-muted">
                  Ajouté par {organizer.addedByEmail} le{" "}
                  {formatDate(organizer.addedAt)}
                </p>
              </div>
              <Button
                variant="outline"
                onClick={() => setRemoving(organizer)}
                aria-label={`Retirer ${organizer.email}`}
              >
                Retirer
              </Button>
            </li>
          ))}
        </ul>
      )}
      <ConfirmDialog
        isOpen={removing !== null}
        onClose={() => setRemoving(null)}
        onConfirm={() => void handleRemoveConfirm()}
        title="Retirer un organisateur"
        confirmLabel="Retirer"
        isDanger
      >
        <p>Retirer {removing?.email} de la liste des organisateurs ?</p>
      </ConfirmDialog>
      {removeError ? (
        <p role="alert" className="py-4 text-danger">
          {removeError}
        </p>
      ) : null}
    </div>
  );
}
