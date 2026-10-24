import { describe, expect, it } from "vitest";
import {
  CONSENT_REQUIRED_MESSAGE,
  DISPLAY_NAME_LENGTH_MESSAGE,
  DISPLAY_NAME_REQUIRED_MESSAGE,
  EMAIL_INVALID_MESSAGE,
  EMAIL_LENGTH_MESSAGE,
  EMAIL_REQUIRED_MESSAGE,
  inscriptionFormSchema,
  parseCreneauxParam,
  selectSlotsByIds,
} from "./inscription";
import type { PlanningSlot } from "./planning";

function slot(id: string, rank: number): PlanningSlot {
  return {
    id,
    rank,
    startsAt: "2099-06-15T18:00:00+00:00",
    endsAt: "2099-06-15T18:30:00+00:00",
    label: "19h00–19h30",
    partOfDay: "evening",
    state: "future",
    textTitle: null,
    textSource: null,
    registrationCount: 0,
  };
}

/** Runs the Standard Schema synchronously and returns its issues. */
function validate(values: unknown, consentRequired = true) {
  const result =
    inscriptionFormSchema(consentRequired)["~standard"].validate(values);

  if (result instanceof Promise) {
    throw new Error("The inscription form schema must validate synchronously.");
  }

  return result.issues ?? [];
}

function issueOn(
  values: unknown,
  field: string,
  consentRequired = true,
): string | undefined {
  return validate(values, consentRequired).find(
    (issue) => issue.path?.[0] === field,
  )?.message;
}

const VALID_VALUES = {
  displayName: "Famille Martin",
  email: "famille.martin@bailletced.org",
  consent: true,
  website: "",
};

describe("parseCreneauxParam (F09-T15)", () => {
  it("splits a comma-separated string", () => {
    expect(parseCreneauxParam("slot-a,slot-b")).toEqual(["slot-a", "slot-b"]);
  });

  it("drops empty segments and trims whitespace", () => {
    expect(parseCreneauxParam(" slot-a ,, slot-b ,")).toEqual([
      "slot-a",
      "slot-b",
    ]);
  });

  it("accepts an already-parsed array and drops non-string items", () => {
    expect(parseCreneauxParam(["slot-a", 42, null, "slot-b"])).toEqual([
      "slot-a",
      "slot-b",
    ]);
  });

  it("deduplicates ids", () => {
    expect(parseCreneauxParam("slot-a,slot-a,slot-b")).toEqual([
      "slot-a",
      "slot-b",
    ]);
  });

  it("returns an empty list when the parameter is missing or empty", () => {
    expect(parseCreneauxParam(undefined)).toEqual([]);
    expect(parseCreneauxParam(null)).toEqual([]);
    expect(parseCreneauxParam("")).toEqual([]);
    expect(parseCreneauxParam(42)).toEqual([]);
  });
});

describe("selectSlotsByIds (F09-T15)", () => {
  it("returns the matching slots in chronological (rank) order", () => {
    const slots = [slot("slot-b", 2), slot("slot-a", 1), slot("slot-c", 3)];

    expect(selectSlotsByIds(slots, ["slot-b", "slot-a"])).toEqual([
      slots[1],
      slots[0],
    ]);
  });

  it("ignores unknown ids", () => {
    expect(selectSlotsByIds([slot("slot-a", 1)], ["slot-unknown"])).toEqual([]);
  });
});

describe("inscriptionFormSchema (F09-T16)", () => {
  it("accepts valid values", () => {
    expect(validate(VALID_VALUES)).toEqual([]);
  });

  it("requires the display name (RG-IN: 1–80 caractères, trim)", () => {
    expect(issueOn({ ...VALID_VALUES, displayName: "" }, "displayName")).toBe(
      DISPLAY_NAME_REQUIRED_MESSAGE,
    );
    expect(
      issueOn({ ...VALID_VALUES, displayName: "   " }, "displayName"),
    ).toBe(DISPLAY_NAME_REQUIRED_MESSAGE);
    expect(
      issueOn({ ...VALID_VALUES, displayName: "x".repeat(81) }, "displayName"),
    ).toBe(DISPLAY_NAME_LENGTH_MESSAGE);
    expect(
      issueOn({ ...VALID_VALUES, displayName: "x".repeat(80) }, "displayName"),
    ).toBeUndefined();
  });

  it("bounds the email (3–254 caractères, format)", () => {
    expect(issueOn({ ...VALID_VALUES, email: "" }, "email")).toBe(
      EMAIL_REQUIRED_MESSAGE,
    );
    expect(issueOn({ ...VALID_VALUES, email: "ab" }, "email")).toBe(
      EMAIL_LENGTH_MESSAGE,
    );
    expect(
      issueOn(
        { ...VALID_VALUES, email: `${"x".repeat(250)}@example.com` },
        "email",
      ),
    ).toBe(EMAIL_LENGTH_MESSAGE);
    expect(issueOn({ ...VALID_VALUES, email: "not-an-email" }, "email")).toBe(
      EMAIL_INVALID_MESSAGE,
    );
  });

  it("requires the consent only when its checkbox is shown (RG-IN-5, RG-IN-7)", () => {
    expect(issueOn({ ...VALID_VALUES, consent: false }, "consent", true)).toBe(
      CONSENT_REQUIRED_MESSAGE,
    );
    // Hidden checkbox (session, consent already given): no consent required
    expect(
      issueOn({ ...VALID_VALUES, consent: false }, "consent", false),
    ).toBeUndefined();
  });

  it("never validates the honeypot field (§6.7)", () => {
    expect(
      issueOn({ ...VALID_VALUES, website: "http://spam.example" }, "website"),
    ).toBeUndefined();
  });
});
