import { CalendarDateTime } from "@internationalized/date";
import { fireEvent, render, screen } from "@testing-library/react";
import { useState } from "react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { toParisIso } from "../lib/parisTime";
import { ParisDateTimeField } from "./ParisDateTimeField";

afterEach(() => {
  vi.unstubAllEnvs();
});

// Controlled harness: the field + the ISO string it would send to the API
function Harness({
  initialValue = null,
  resolvedLabel,
}: {
  initialValue?: CalendarDateTime | null;
  resolvedLabel?: string | null;
}) {
  const [value, setValue] = useState<CalendarDateTime | null>(initialValue);

  return (
    <div>
      <ParisDateTimeField
        label="Début (heure de Paris)"
        value={value}
        onChange={setValue}
        resolvedLabel={resolvedLabel}
      />
      <output data-testid="sent">{value ? toParisIso(value) : ""}</output>
    </div>
  );
}

function fillDateAndTime(date: string, time: string) {
  fireEvent.change(screen.getByLabelText("Début (heure de Paris) — date"), {
    target: { value: date },
  });
  fireEvent.change(screen.getByLabelText("Début (heure de Paris) — heure"), {
    target: { value: time },
  });
}

describe("ParisDateTimeField", () => {
  it.each([
    ["Europe/Paris", -60],
    ["America/Montreal", 300],
  ])(
    "sends the same Paris time whatever the simulated browser time zone (%s)",
    (timeZone, expectedOffsetMinutes) => {
      vi.stubEnv("TZ", timeZone);

      // Sanity check: the simulated browser zone really applies
      expect(new Date("2026-11-28T19:00:00+00:00").getTimezoneOffset()).toBe(
        expectedOffsetMinutes,
      );

      render(<Harness />);

      fillDateAndTime("2026-11-28", "20:00");

      // 20:00 in Paris = 19:00 UTC in November (CET, UTC+1)
      expect(screen.getByTestId("sent")).toHaveTextContent(
        "2026-11-28T20:00:00+01:00",
      );
    },
  );

  it("emits nothing until both the date and the time are set", () => {
    render(<Harness />);

    fireEvent.change(screen.getByLabelText("Début (heure de Paris) — date"), {
      target: { value: "2026-11-28" },
    });
    expect(screen.getByTestId("sent")).toHaveTextContent("");

    fireEvent.change(screen.getByLabelText("Début (heure de Paris) — heure"), {
      target: { value: "20:00" },
    });
    expect(screen.getByTestId("sent")).toHaveTextContent(
      "2026-11-28T20:00:00+01:00",
    );
  });

  it("emits null again when a part is cleared", () => {
    render(<Harness />);

    fillDateAndTime("2026-11-28", "20:00");
    expect(screen.getByTestId("sent")).toHaveTextContent(
      "2026-11-28T20:00:00+01:00",
    );

    fireEvent.change(screen.getByLabelText("Début (heure de Paris) — date"), {
      target: { value: "" },
    });
    expect(screen.getByTestId("sent")).toHaveTextContent("");
    // The typed time is kept while the date is missing
    expect(
      screen.getByLabelText<HTMLInputElement>("Début (heure de Paris) — heure")
        .value,
    ).toBe("20:00");
  });

  it("pre-fills the inputs from an initial value", () => {
    render(
      <Harness initialValue={new CalendarDateTime(2026, 11, 28, 20, 0)} />,
    );

    expect(
      screen.getByLabelText<HTMLInputElement>("Début (heure de Paris) — date")
        .value,
    ).toBe("2026-11-28");
    expect(
      screen.getByLabelText<HTMLInputElement>("Début (heure de Paris) — heure")
        .value,
    ).toBe("20:00");
  });

  it("shows the time retained by the API when it adjusted the entry (RG-ED-7)", () => {
    render(<Harness resolvedLabel="3h30–4h00" />);

    expect(screen.getByText("Heure retenue : 3h30–4h00")).toBeInTheDocument();
  });

  it("hides the retained time when the API did not adjust the entry", () => {
    render(<Harness />);

    expect(screen.queryByText(/Heure retenue/)).not.toBeInTheDocument();
  });

  it("shows the field error and flags the inputs as invalid", () => {
    render(
      <ParisDateTimeField
        label="Début (heure de Paris)"
        value={null}
        onChange={() => undefined}
        isInvalid
        errorMessage="L'heure de début est obligatoire."
      />,
    );

    expect(screen.getByRole("alert")).toHaveTextContent(
      "L'heure de début est obligatoire.",
    );
    expect(
      screen.getByLabelText("Début (heure de Paris) — date"),
    ).toHaveAttribute("aria-invalid", "true");
    expect(
      screen.getByLabelText("Début (heure de Paris) — heure"),
    ).toHaveAttribute("aria-invalid", "true");
  });
});
