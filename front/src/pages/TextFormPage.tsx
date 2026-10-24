import {
  Button,
  Description,
  FieldError,
  Input,
  Label,
  TextArea,
  TextField,
} from "@heroui/react";
import { Link, useNavigate, useParams } from "@tanstack/react-router";
import { type FormEvent, useEffect, useRef, useState } from "react";
import {
  type ApiError,
  getApiErrorDetail,
  getFieldErrors,
  isValidationError,
} from "../api/errors";
import {
  useCreateText,
  useDeleteText,
  useText,
  useUpdateText,
} from "../api/hooks";
import { ConfirmDialog } from "../components/ConfirmDialog";
import { ErrorState } from "../components/ErrorState";
import { LoadingState } from "../components/LoadingState";
import { notify } from "../components/Notifications";
import { PageTitle } from "../components/PageTitle";
import { RichTextEditor } from "../components/RichTextEditor";
import { RichTextView } from "../components/RichTextView";

interface TextFieldErrors {
  title?: string;
  source?: string;
  body?: string;
  hints?: string;
}

/**
 * Text form (F06-T13, US-06.1, US-06.2): creation (/organisation/texts/new)
 * and edition (/organisation/texts/$textId/edit). The body is edited with
 * the limited rich text editor and previewed as the priants will read it;
 * the server cleans it again and answers 422 per field.
 *
 * An existing text can be deleted after confirmation (F06-T14, US-06.3): the
 * API refuses with 409 while a scheduled edition carries it.
 */
function TextForm({ textId }: { textId: string | null }) {
  const isEdit = null !== textId;
  const textQuery = useText(textId);
  const createText = useCreateText();
  const updateText = useUpdateText();
  const deleteText = useDeleteText();
  const navigate = useNavigate();
  const [confirmingDelete, setConfirmingDelete] = useState(false);

  const [title, setTitle] = useState("");
  const [source, setSource] = useState("");
  const [body, setBody] = useState("");
  const [hints, setHints] = useState("");
  const [fieldErrors, setFieldErrors] = useState<TextFieldErrors>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  // Pre-fill runs once per loaded text, never on the user's own edits
  const initializedForRef = useRef<string | null>(null);

  const text = textQuery.data;

  useEffect(() => {
    if (!text?.id || initializedForRef.current === text.id) {
      return;
    }

    initializedForRef.current = text.id;
    setTitle(text.title ?? "");
    setSource(text.source ?? "");
    setBody(text.body ?? "");
    setHints(text.hints ?? "");
  }, [text]);

  const isPending =
    createText.isPending || updateText.isPending || deleteText.isPending;

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    setFieldErrors({});
    setFormError(null);
    setSuccessMessage(null);

    const values = {
      title: title.trim(),
      source: source.trim(),
      body,
      hints: hints.trim(),
    };

    try {
      if (isEdit && textId) {
        await updateText.mutateAsync({ id: textId, ...values });
        setSuccessMessage("Modifications enregistrées.");
      } else {
        await createText.mutateAsync(values);
        notify("Texte créé.", { variant: "success" });
        await navigate({ to: "/organisation/texts" });
      }
    } catch (error) {
      if (isValidationError(error as ApiError)) {
        // Each 422 violation lands on its own field
        setFieldErrors(getFieldErrors(error as ApiError));
      } else {
        setFormError(
          getApiErrorDetail(error) ??
            "Le texte n'a pas pu être enregistré. Veuillez réessayer.",
        );
      }
    }
  }

  async function handleDeleteConfirm() {
    setConfirmingDelete(false);
    setFormError(null);
    setSuccessMessage(null);

    if (!textId) {
      return;
    }

    try {
      await deleteText.mutateAsync(textId);
      notify("Texte supprimé.", { variant: "success" });
      await navigate({ to: "/organisation/texts" });
    } catch (error) {
      setFormError(
        getApiErrorDetail(error) ??
          "Le texte n'a pas pu être supprimé. Veuillez réessayer.",
      );
    }
  }

  const heading = isEdit ? "Modifier le texte" : "Nouveau texte";

  if (isEdit && (textQuery.isLoading || textQuery.isError || !text)) {
    return (
      <div className="mx-auto w-full max-w-3xl">
        <PageTitle>{heading}</PageTitle>
        {textQuery.isLoading ? (
          <LoadingState />
        ) : (
          <ErrorState
            message="Le texte n'a pas pu être chargé."
            onRetry={() => void textQuery.refetch()}
          />
        )}
      </div>
    );
  }

  const attributionCount = text?.attributionCount ?? 0;

  return (
    <div className="mx-auto w-full max-w-3xl">
      <PageTitle>{heading}</PageTitle>
      {isEdit && attributionCount > 0 ? (
        <p className="mt-4 text-muted">
          {attributionCount === 1
            ? "Attribué à 1 créneau : la correction s'y appliquera aussi."
            : `Attribué à ${attributionCount} créneaux : la correction s'y appliquera aussi.`}
        </p>
      ) : null}
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
            disabled={isPending}
          />
          <FieldError>{fieldErrors.title}</FieldError>
        </TextField>
        <TextField
          isInvalid={Boolean(fieldErrors.source)}
          className="w-full max-w-md"
        >
          <Label>Source</Label>
          <Input
            value={source}
            onChange={(event) => setSource(event.target.value)}
            maxLength={255}
            disabled={isPending}
          />
          <Description>Auteur, œuvre ou livre biblique.</Description>
          <FieldError>{fieldErrors.source}</FieldError>
        </TextField>
        <RichTextEditor
          label="Corps du texte"
          value={body}
          onChange={setBody}
          description="Maj + Entrée pour aller à la ligne sans changer de paragraphe (psaumes)."
          errorMessage={fieldErrors.body}
          isDisabled={isPending}
        />
        <section
          aria-labelledby="text-preview-title"
          className="w-full rounded-2xl border border-separator bg-surface p-6"
        >
          <h2 id="text-preview-title" className="mb-4 text-xl">
            Aperçu
          </h2>
          {body ? (
            <RichTextView html={body} />
          ) : (
            <p className="text-muted">Le corps du texte s'affichera ici.</p>
          )}
        </section>
        <TextField
          isInvalid={Boolean(fieldErrors.hints)}
          className="w-full max-w-2xl"
        >
          <Label>Pistes de méditation</Label>
          <TextArea
            value={hints}
            onChange={(event) => setHints(event.target.value)}
            maxLength={2000}
            rows={4}
            disabled={isPending}
          />
          <Description>
            Facultatif : questions ou intentions pour prier.
          </Description>
          <FieldError>{fieldErrors.hints}</FieldError>
        </TextField>
        <div className="flex flex-wrap items-center gap-4">
          <Button type="submit" variant="primary" isDisabled={isPending}>
            {isPending
              ? "Enregistrement…"
              : isEdit
                ? "Enregistrer les modifications"
                : "Créer le texte"}
          </Button>
          <Link
            to="/organisation/texts"
            className="text-link underline underline-offset-4"
          >
            Retour à la bibliothèque
          </Link>
          {isEdit ? (
            <Button
              variant="danger-soft"
              isDisabled={isPending}
              onPress={() => setConfirmingDelete(true)}
            >
              Supprimer le texte
            </Button>
          ) : null}
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
      <ConfirmDialog
        isOpen={confirmingDelete}
        onClose={() => setConfirmingDelete(false)}
        onConfirm={() => void handleDeleteConfirm()}
        title="Supprimer le texte"
        confirmLabel="Supprimer"
        isDanger
      >
        <p>Supprimer « {text?.title} » de la bibliothèque ?</p>
      </ConfirmDialog>
    </div>
  );
}

/** /organisation/texts/new (F06-T13): text creation form. */
export function TextFormPage() {
  return <TextForm textId={null} />;
}

/** /organisation/texts/$textId/edit (F06-T13): text edit form. */
export function TextEditPage() {
  const { textId } = useParams({ from: "/organisation/texts/$textId/edit" });

  return <TextForm textId={textId} />;
}
