import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { EmptyState } from "./EmptyState";
import { ErrorState } from "./ErrorState";
import { LoadingState } from "./LoadingState";

describe("LoadingState (F01-T08)", () => {
  it("announces the label politely (role=status)", () => {
    render(<LoadingState label="Chargement de votre espace…" />);

    expect(screen.getByRole("status")).toHaveTextContent(
      "Chargement de votre espace…",
    );
  });

  it("defaults to a generic label", () => {
    render(<LoadingState />);

    expect(screen.getByRole("status")).toHaveTextContent("Chargement…");
  });
});

describe("ErrorState (F01-T08)", () => {
  it("announces the message assertively (role=alert)", () => {
    render(<ErrorState message="Une erreur est survenue." />);

    expect(screen.getByRole("alert")).toHaveTextContent(
      "Une erreur est survenue.",
    );
  });

  it("offers a retry action when onRetry is given", () => {
    const onRetry = vi.fn();
    render(<ErrorState message="Erreur" onRetry={onRetry} />);

    fireEvent.click(screen.getByRole("button", { name: "Réessayer" }));
    expect(onRetry).toHaveBeenCalledOnce();
  });

  it("renders no action without onRetry", () => {
    render(<ErrorState message="Erreur" />);

    expect(screen.queryByRole("button")).toBeNull();
  });
});

describe("EmptyState (F01-T08)", () => {
  it("shows a muted message with an optional action", () => {
    const onAction = vi.fn();
    render(
      <EmptyState
        message="Aucun créneau disponible."
        actionLabel="Voir le planning"
        onAction={onAction}
      />,
    );

    expect(screen.getByText("Aucun créneau disponible.")).toBeInTheDocument();
    fireEvent.click(screen.getByRole("button", { name: "Voir le planning" }));
    expect(onAction).toHaveBeenCalledOnce();
  });

  it("renders no action without one", () => {
    render(<EmptyState message="Rien à afficher." />);

    expect(screen.queryByRole("button")).toBeNull();
  });
});
