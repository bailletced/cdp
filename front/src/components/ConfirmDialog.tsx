import { AlertDialog, Button } from "@heroui/react";
import type { ReactNode } from "react";

interface ConfirmDialogProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => void;
  title: string;
  children: ReactNode;
  confirmLabel?: string;
  cancelLabel?: string;
  isDanger?: boolean;
}

/**
 * Confirmation dialog (F01-T09) built on React Aria (HeroUI AlertDialog):
 * role alertdialog, focus trapped inside, Escape cancels. The backdrop is
 * not dismissable — a confirmation always requires an explicit action.
 */
export function ConfirmDialog({
  isOpen,
  onClose,
  onConfirm,
  title,
  children,
  confirmLabel = "Confirmer",
  cancelLabel = "Annuler",
  isDanger = false,
}: ConfirmDialogProps) {
  return (
    <AlertDialog
      isOpen={isOpen}
      onOpenChange={(open) => {
        if (!open) {
          onClose();
        }
      }}
    >
      {/* The container (the modal) lives inside the backdrop (the overlay):
          side by side, the backdrop never finished its exit and kept
          intercepting the clicks on the page after a confirmation */}
      <AlertDialog.Backdrop isKeyboardDismissDisabled={false}>
        <AlertDialog.Container>
          <AlertDialog.Dialog>
            <AlertDialog.Header>
              <AlertDialog.Heading>{title}</AlertDialog.Heading>
            </AlertDialog.Header>
            <AlertDialog.Body>{children}</AlertDialog.Body>
            <AlertDialog.Footer>
              <Button variant="outline" onClick={onClose}>
                {cancelLabel}
              </Button>
              <Button
                variant={isDanger ? "danger" : "primary"}
                onClick={onConfirm}
              >
                {confirmLabel}
              </Button>
            </AlertDialog.Footer>
          </AlertDialog.Dialog>
        </AlertDialog.Container>
      </AlertDialog.Backdrop>
    </AlertDialog>
  );
}
