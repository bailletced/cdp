import {
  Button,
  FieldError,
  Input,
  Label,
  Modal,
  TextField,
} from "@heroui/react";
import { type FormEvent, useState } from "react";
import { getApiErrorStatus } from "../api/errors";
import { useRenameMe } from "../api/hooks";
import { notify } from "./Notifications";

/** The message of the first violation on displayName, if any. */
function displayNameViolation(error: unknown): string | null {
  if (
    getApiErrorStatus(error) !== 422 ||
    typeof error !== "object" ||
    error === null ||
    !("violations" in error) ||
    !Array.isArray(error.violations)
  ) {
    return null;
  }

  const violation = error.violations.find(
    (item: { propertyPath?: unknown }) => item?.propertyPath === "displayName",
  );

  return typeof violation?.message === "string" ? violation.message : null;
}

/**
 * « Changer mon nom » (F14-T08, US-14.2): the name is carried by the
 * priant, so it changes everywhere at once — the dialog says so. A refusal
 * (422) shows on the field; a success closes the dialog and is announced.
 */
export function RenameDialog({ currentName }: { currentName: string }) {
  const [isOpen, setIsOpen] = useState(false);
  const [name, setName] = useState(currentName);
  const [fieldError, setFieldError] = useState<string | null>(null);
  const renameMe = useRenameMe();

  function open() {
    setName(currentName);
    setFieldError(null);
    setIsOpen(true);
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFieldError(null);

    try {
      await renameMe.mutateAsync(name);
      setIsOpen(false);
      notify("Votre nom a été changé.", { variant: "success" });
    } catch (error) {
      setFieldError(
        displayNameViolation(error) ??
          "Votre nom n'a pas pu être changé. Réessayez dans un instant.",
      );
    }
  }

  return (
    <>
      <Button variant="outline" onClick={open}>
        Changer mon nom
      </Button>
      <Modal isOpen={isOpen} onOpenChange={setIsOpen}>
        <Modal.Backdrop isDismissable>
          <Modal.Container>
            <Modal.Dialog>
              <form onSubmit={(event) => void handleSubmit(event)} noValidate>
                <Modal.Header>
                  <Modal.Heading>Changer mon nom</Modal.Heading>
                </Modal.Header>
                <Modal.Body className="grid gap-4">
                  <p>
                    Votre nom s'applique partout : dans le planning, le fil du
                    relais, les intentions signées et l'historique.
                  </p>
                  <TextField isInvalid={fieldError !== null}>
                    <Label>Nom affiché</Label>
                    <Input
                      name="displayName"
                      autoComplete="name"
                      value={name}
                      onChange={(event) => setName(event.target.value)}
                    />
                    <FieldError>{fieldError}</FieldError>
                  </TextField>
                </Modal.Body>
                <Modal.Footer>
                  <Button variant="tertiary" onClick={() => setIsOpen(false)}>
                    Annuler
                  </Button>
                  <Button
                    type="submit"
                    variant="primary"
                    isPending={renameMe.isPending}
                  >
                    Enregistrer
                  </Button>
                </Modal.Footer>
              </form>
            </Modal.Dialog>
          </Modal.Container>
        </Modal.Backdrop>
      </Modal>
    </>
  );
}
