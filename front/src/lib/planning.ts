/**
 * Public planning slot (F08-T04, US-08.2): one entry of the payload of
 * GET /api/editions/{id}/slots. Instants are UTC ISO 8601 strings, the
 * label is the Paris wall-clock range (« 19h00–19h30 »), the state is
 * computed by the API against the server clock. registrationCount is the
 * number of priants on the slot (RG-PL-1, F09-T13).
 *
 * F10: participants are the names and pointages of the priants, given only
 * to the inscrits of the edition and the organizers — null otherwise
 * (RG-PL-4). mine marks the slots of the viewer (session or device pass).
 */
export interface PlanningSlot {
  id: string;
  rank: number;
  startsAt: string;
  endsAt: string;
  label: string;
  partOfDay: string;
  state: string;
  textTitle: string | null;
  textSource: string | null;
  registrationCount: number;
  participants: PlanningParticipant[] | null;
  mine: boolean;
}

/** A priant registered on a slot, and whether they have prayed (RG-LV-2). */
export interface PlanningParticipant {
  name: string;
  prayed: boolean;
}

/** Temporal state of a slot: past, current (running now) or future. */
export type SlotTemporalState = "past" | "current" | "future";

/**
 * The temporal state of a slot at a given instant (US-08.4): computed with
 * the server-corrected clock, so a visitor whose device clock is skewed
 * still sees the right state. A slot is past once it has ended.
 */
export function slotTemporalState(
  slot: Pick<PlanningSlot, "startsAt" | "endsAt">,
  nowMs: number,
): SlotTemporalState {
  const startsAt = new Date(slot.startsAt).getTime();
  const endsAt = new Date(slot.endsAt).getTime();

  if (nowMs >= endsAt) {
    return "past";
  }

  if (nowMs >= startsAt) {
    return "current";
  }

  return "future";
}

function isPlanningSlot(
  slot: unknown,
): slot is Omit<PlanningSlot, "registrationCount" | "participants" | "mine"> &
  Record<string, unknown> {
  if (typeof slot !== "object" || slot === null) {
    return false;
  }

  const candidate = slot as Record<string, unknown>;

  return (
    typeof candidate.id === "string" &&
    typeof candidate.rank === "number" &&
    typeof candidate.startsAt === "string" &&
    typeof candidate.endsAt === "string" &&
    typeof candidate.label === "string" &&
    typeof candidate.partOfDay === "string" &&
    typeof candidate.state === "string" &&
    (candidate.textTitle === null ||
      candidate.textTitle === undefined ||
      typeof candidate.textTitle === "string") &&
    (candidate.textSource === null ||
      candidate.textSource === undefined ||
      typeof candidate.textSource === "string")
  );
}

function isParticipant(
  participant: unknown,
): participant is PlanningParticipant {
  if (typeof participant !== "object" || participant === null) {
    return false;
  }

  const candidate = participant as Record<string, unknown>;

  return (
    typeof candidate.name === "string" && typeof candidate.prayed === "boolean"
  );
}

/** Masked participants stay null: only a list means the names are visible. */
function parseParticipants(
  participants: unknown,
): PlanningParticipant[] | null {
  if (!Array.isArray(participants)) {
    return null;
  }

  return participants
    .filter(isParticipant)
    .map(({ name, prayed }) => ({ name, prayed }));
}

/**
 * The OpenAPI schema types slots as unknown[]: narrow them to PlanningSlot,
 * dropping malformed entries instead of failing the whole grid.
 */
export function parsePlanningSlots(slots: unknown): PlanningSlot[] {
  if (!Array.isArray(slots)) {
    return [];
  }

  return slots.filter(isPlanningSlot).map((slot) => ({
    id: slot.id,
    rank: slot.rank,
    startsAt: slot.startsAt,
    endsAt: slot.endsAt,
    label: slot.label,
    partOfDay: slot.partOfDay,
    state: slot.state,
    textTitle: slot.textTitle ?? null,
    textSource: slot.textSource ?? null,
    registrationCount:
      typeof slot.registrationCount === "number" ? slot.registrationCount : 0,
    participants: parseParticipants(slot.participants),
    mine: slot.mine === true,
  }));
}
