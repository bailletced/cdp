import {
  Button,
  Description,
  FieldError,
  Input,
  Label,
  TextArea,
  TextField,
} from "@heroui/react";
import type { CalendarDateTime } from "@internationalized/date";
import { Link, useParams } from "@tanstack/react-router";
import { type FormEvent, useEffect, useRef, useState } from "react";
import {
  type ApiError,
  getApiErrorDetail,
  getApiErrorStatus,
  getFieldErrors,
  isValidationError,
} from "../api/errors";
import { useCreateEdition, useEdition, useUpdateEdition } from "../api/hooks";
import { ErrorState } from "../components/ErrorState";
import { LoadingState } from "../components/LoadingState";
import { PageTitle } from "../components/PageTitle";
import { ParisDateTimeField } from "../components/ParisDateTimeField";
import type { EditionView } from "../lib/editions";
import { parisCalendarDateTimeFromIso, toParisIso } from "../lib/parisTime";

interface EditionFieldErrors {
  title?: string;
  presentation?: string;
  startsAt?: string;
  opensAt?: string;
}

function pad2(n: number): string {
  return String(n).padStart(2, "0");
}

/**
 * The start label resolved by the API (RG-ED-7) is only worth showing when it
 * differs from what was typed: a nonexistent wall clock (spring forward) is
 * shifted forward by the API.
 */
function retainedStartLabel(
  response: Pick<EditionView, "resolvedStartsAtLabel"> | undefined,
  entered: CalendarDateTime,
): string | null {
  const label = response?.resolvedStartsAtLabel;

  if (!label) {
    return null;
  }

  return label.startsWith(`${entered.hour}h${pad2(entered.minute)}`)
    ? null
    : label;
}

/**
 * Edition form (F05-T20): creation (/organisation/editions/new) and edition
 * (/organisation/editions/$editionId/edit). Dates are entered as a Paris wall
 * clock and sent as ISO 8601 with the explicit Europe/Paris offset (spec §4.6);
 * the edit form sends back the version read on load (optimistic lock, 409 on
 * conflict).
 */
function EditionForm({ editionId }: { editionId: string | null }) {
  const isEdit = null !== editionId;
  const editionQuery = useEdition(editionId);
  const createEdition = useCreateEdition();
  const updateEdition = useUpdateEdition();

  const [title, setTitle] = useState("");
  const [presentation, setPresentation] = useState("");
  const [startsAt, setStartsAt] = useState<CalendarDateTime | null>(null);
  const [opensAt, setOpensAt] = useState<CalendarDateTime | null>(null);
  const [version, setVersion] = useState<number | null>(null);
  const [fieldErrors, setFieldErrors] = useState<EditionFieldErrors>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [resolvedStartsAtLabel, setResolvedStartsAtLabel] = useState<
    string | null
  >(null);
  // Pre-fill runs once per loaded edition, never on the user's own edits
  const initializedForRef = useRef<string | null>(null);

  const edition = editionQuery.data;

  useEffect(() => {
    if (!edition?.id || initializedForRef.current === edition.id) {
      return;
    }

    initializedForRef.current = edition.id;
    setTitle(edition.title ?? "");
    setPresentation(edition.presentation ?? "");
    setStartsAt(parisCalendarDateTimeFromIso(edition.startsAt));
    setOpensAt(parisCalendarDateTimeFromIso(edition.opensAt));
    setVersion(edition.version ?? null);
  }, [edition]);

  const isPending = createEdition.isPending || updateEdition.isPending;

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    setFormError(null);
    setSuccessMessage(null);
    setResolvedStartsAtLabel(null);

    // Client-side required check: the dates have no native input constraint,
    // the message names the missing field
    if (!startsAt || !opensAt) {
      setFieldErrors({
        ...(startsAt
          ? {}
          : {
              startsAt:
                "La date et l'heure du début de l'édition sont requises.",
            }),
        ...(opensAt
          ? {}
          : {
              opensAt:
                "La date et l'heure de l'ouverture des inscriptions sont requises.",
            }),
      });

      return;
    }

    const values = {
      title: title.trim(),
      presentation: presentation.trim(),
      startsAt: toParisIso(startsAt),
      opensAt: toParisIso(opensAt),
    };

    try {
      if (isEdit && editionId) {
        const updated = await updateEdition.mutateAsync({
          id: editionId,
          version: version ?? 1,
          ...values,
        });

        setVersion(updated?.version ?? version);
        setSuccessMessage("Modifications enregistrées.");
        setResolvedStartsAtLabel(retainedStartLabel(updated, startsAt));
      } else {
        const created = await createEdition.mutateAsync(values);

        setSuccessMessage("Édition créée.");
        setResolvedStartsAtLabel(retainedStartLabel(created, startsAt));
      }
    } catch (error) {
      if (isValidationError(error as ApiError)) {
        // Each 422 violation lands on its own field
        setFieldErrors(getFieldErrors(error as ApiError));
      } else if (getApiErrorStatus(error) === 409) {
        setFormError(
          getApiErrorDetail(error) ??
            "Cette édition a été modifiée entre-temps. Rechargez la page pour voir les dernières modifications.",
        );
      } else {
        setFormError(
          getApiErrorDetail(error) ??
            "L'édition n'a pas pu être enregistrée. Veuillez réessayer.",
        );
      }
    }
  }

  if (isEdit) {
    if (editionQuery.isLoading) {
      return (
        <div className="mx-auto w-full max-w-3xl">
          <PageTitle>Modifier l'édition</PageTitle>
          <LoadingState />
        </div>
      );
    }

    if (editionQuery.isError || !edition) {
      return (
        <div className="mx-auto w-full max-w-3xl">
          <PageTitle>Modifier l'édition</PageTitle>
          <ErrorState
            message="L'édition n'a pas pu être chargée."
            onRetry={() => void editionQuery.refetch()}
          />
        </div>
      );
    }
  }

  return (
    <div className="mx-auto w-full max-w-3xl">
      <PageTitle>
        {isEdit ? "Modifier l'édition" : "Nouvelle édition"}
      </PageTitle>
      <form
        onSubmit={handleSubmit}
        className="flex flex-col items-start gap-6 py-6"
      >
        <TextField
          isInvalid={Boolean(fieldErrors.title)}
          className="w-full max-w-md"
        >
          <Label>Titre</Label>
          <Input
            value={title}
            onChange={(event) => setTitle(event.target.value)}
            maxLength={120}
            required
            disabled={isPending}
          />
          <FieldError>{fieldErrors.title}</FieldError>
        </TextField>
        <TextField
          isInvalid={Boolean(fieldErrors.presentation)}
          className="w-full max-w-2xl"
        >
          <Label>Présentation</Label>
          <TextArea
            value={presentation}
            onChange={(event) => setPresentation(event.target.value)}
            maxLength={2000}
            rows={4}
            disabled={isPending}
          />
          <Description>
            Texte affiché sur la page publique de l'édition.
          </Description>
          <FieldError>{fieldErrors.presentation}</FieldError>
        </TextField>
        <ParisDateTimeField
          label="Début (heure de Paris)"
          description="Sur l'heure pleine ou la demi-heure, ex. 20h00 ou 20h30."
          value={startsAt}
          onChange={setStartsAt}
          resolvedLabel={resolvedStartsAtLabel}
          isInvalid={Boolean(fieldErrors.startsAt)}
          errorMessage={fieldErrors.startsAt}
          isDisabled={isPending}
        />
        <ParisDateTimeField
          label="Ouverture des inscriptions (heure de Paris)"
          description="Antérieure au début de l'édition."
          value={opensAt}
          onChange={setOpensAt}
          isInvalid={Boolean(fieldErrors.opensAt)}
          errorMessage={fieldErrors.opensAt}
          isDisabled={isPending}
        />
        <div className="flex flex-wrap items-center gap-4">
          <Button type="submit" variant="primary" isDisabled={isPending}>
            {isPending
              ? "Enregistrement…"
              : isEdit
                ? "Enregistrer les modifications"
                : "Créer l'édition"}
          </Button>
          <Link
            to="/organisation/editions"
            className="text-link underline underline-offset-4"
          >
            Retour à la liste des éditions
          </Link>
        </div>
        {formError ? (
          <p role="alert" className="text-danger">
            {formError}
          </p>
        ) : null}
        {successMessage ? (
          <output className="text-success">{successMessage}</output>
        ) : null}
      </form>
    </div>
  );
}

/** /organisation/editions/new (F05-T20): edition creation form. */
export function EditionFormPage() {
  return <EditionForm editionId={null} />;
}

/** /organisation/editions/$editionId/edit (F05-T20): edition edit form. */
export function EditionEditPage() {
  const { editionId } = useParams({
    from: "/organisation/editions/$editionId/edit",
  });

  return <EditionForm editionId={editionId} />;
}
