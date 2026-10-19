import { toast } from "@heroui/react";
import {
  act,
  fireEvent,
  render,
  screen,
  waitFor,
} from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { NOTIFICATION_TIMEOUT, Notifications, notify } from "./Notifications";

const MESSAGE = "Votre lien de connexion a été envoyé.";

// Harness: the toast region plus a button that fires a notification.
function Harness({ timeout }: { timeout?: number }) {
  return (
    <>
      <Notifications />
      <button
        type="button"
        onClick={() => notify(MESSAGE, timeout ? { timeout } : {})}
      >
        Notifier
      </button>
    </>
  );
}

describe("Notifications (F01-T10)", () => {
  // The toast queue is a module-level singleton: empty it between tests.
  afterEach(() => {
    vi.useRealTimers();
    toast.clear();
  });

  it("announces the message in a live region (role=alert)", async () => {
    render(<Harness />);

    fireEvent.click(screen.getByRole("button", { name: "Notifier" }));

    const announcement = await screen.findByRole("alert");
    expect(announcement).toHaveTextContent(MESSAGE);
  });

  it("dismisses the notification with its keyboard-operable close button", async () => {
    render(<Harness />);

    fireEvent.click(screen.getByRole("button", { name: "Notifier" }));

    const close = await screen.findByRole("button", {
      name: "Fermer la notification",
    });
    fireEvent.click(close);

    await waitFor(() => {
      expect(screen.queryByRole("alert")).not.toBeInTheDocument();
    });
  });

  it("keeps the notification for the full timeout, then auto-dismisses it", async () => {
    vi.useFakeTimers();
    render(<Harness />);

    fireEvent.click(screen.getByRole("button", { name: "Notifier" }));
    // Flush the queue update: the toast renders
    await act(async () => {});
    expect(screen.getByRole("alert")).toHaveTextContent(MESSAGE);

    // Still there just before the timeout (sufficient reading duration)
    act(() => {
      vi.advanceTimersByTime(NOTIFICATION_TIMEOUT - 1000);
    });
    expect(screen.getByRole("alert")).toBeInTheDocument();

    // Gone once the timeout (plus the exit delay) has elapsed
    act(() => {
      vi.advanceTimersByTime(2000);
    });
    expect(screen.queryByRole("alert")).not.toBeInTheDocument();
  });
});
