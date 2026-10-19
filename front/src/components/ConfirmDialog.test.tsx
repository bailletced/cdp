import { fireEvent, render, screen } from "@testing-library/react";
import { useState } from "react";
import { describe, expect, it, vi } from "vitest";
import { ConfirmDialog } from "./ConfirmDialog";

function Harness({
  onConfirm,
  onClose,
}: {
  onConfirm: () => void;
  onClose: () => void;
}) {
  const [isOpen, setIsOpen] = useState(false);
  return (
    <>
      <button type="button" onClick={() => setIsOpen(true)}>
        Ouvrir
      </button>
      <ConfirmDialog
        isOpen={isOpen}
        onClose={() => {
          setIsOpen(false);
          onClose();
        }}
        onConfirm={() => {
          setIsOpen(false);
          onConfirm();
        }}
        title="Me désinscrire"
        confirmLabel="Me désinscrire"
        isDanger
      >
        Voulez-vous vraiment vous désinscrire de ce créneau ?
      </ConfirmDialog>
    </>
  );
}

describe("ConfirmDialog (F01-T09)", () => {
  it("renders nothing when closed", () => {
    render(<Harness onConfirm={vi.fn()} onClose={vi.fn()} />);

    expect(screen.queryByRole("alertdialog")).toBeNull();
  });

  it("opens as an alertdialog with the message and both actions", async () => {
    render(<Harness onConfirm={vi.fn()} onClose={vi.fn()} />);

    fireEvent.click(screen.getByRole("button", { name: "Ouvrir" }));

    const dialog = await screen.findByRole("alertdialog");
    expect(dialog).toHaveTextContent(
      "Voulez-vous vraiment vous désinscrire de ce créneau ?",
    );
    expect(
      screen.getByRole("button", { name: "Me désinscrire" }),
    ).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "Annuler" })).toBeInTheDocument();
  });

  it("moves the focus inside the dialog when it opens", async () => {
    render(<Harness onConfirm={vi.fn()} onClose={vi.fn()} />);

    fireEvent.click(screen.getByRole("button", { name: "Ouvrir" }));

    const dialog = await screen.findByRole("alertdialog");
    expect(dialog.contains(document.activeElement)).toBe(true);
  });

  it("confirms and closes", async () => {
    const onConfirm = vi.fn();
    render(<Harness onConfirm={onConfirm} onClose={vi.fn()} />);

    fireEvent.click(screen.getByRole("button", { name: "Ouvrir" }));
    fireEvent.click(
      await screen.findByRole("button", { name: "Me désinscrire" }),
    );

    expect(onConfirm).toHaveBeenCalledOnce();
    expect(screen.queryByRole("alertdialog")).toBeNull();
  });

  it("cancels with the Annuler button", async () => {
    const onClose = vi.fn();
    render(<Harness onConfirm={vi.fn()} onClose={onClose} />);

    fireEvent.click(screen.getByRole("button", { name: "Ouvrir" }));
    fireEvent.click(await screen.findByRole("button", { name: "Annuler" }));

    expect(onClose).toHaveBeenCalledOnce();
    expect(screen.queryByRole("alertdialog")).toBeNull();
  });

  it("cancels with the Escape key", async () => {
    const onClose = vi.fn();
    render(<Harness onConfirm={vi.fn()} onClose={onClose} />);

    fireEvent.click(screen.getByRole("button", { name: "Ouvrir" }));
    const dialog = await screen.findByRole("alertdialog");
    fireEvent.keyDown(dialog, { key: "Escape" });

    expect(onClose).toHaveBeenCalledOnce();
  });
});
