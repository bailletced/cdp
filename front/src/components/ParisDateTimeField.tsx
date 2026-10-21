import { Input } from "@heroui/react";
import {
  type CalendarDate,
  CalendarDateTime,
  type Time,
  parseDate,
  parseTime,
} from "@internationalized/date";
import { type ChangeEvent, useEffect, useId, useRef, useState } from "react";

interface ParisDateTimeFieldProps {
  /** Visible label of the field, e.g. "Début (heure de Paris)". */
  label: string;
  /** Wall clock value (zone-free), as entered in Paris time. */
  value: CalendarDateTime | null;
  onChange: (value: CalendarDateTime | null) => void;
  /** Help text under the inputs. */
  description?: string;
  /** Label resolved by the API when it adjusted the entered time (RG-ED-7). */
  resolvedLabel?: string | null;
  isInvalid?: boolean;
  errorMessage?: string;
  isDisabled?: boolean;
}

function pad2(n: number): string {
  return String(n).padStart(2, "0");
}

function toDateInputValue(value: CalendarDateTime | null): string {
  return value ? `${value.year}-${pad2(value.month)}-${pad2(value.day)}` : "";
}

function toTimeInputValue(value: CalendarDateTime | null): string {
  return value ? `${pad2(value.hour)}:${pad2(value.minute)}` : "";
}

function safeParseDate(text: string): CalendarDate | null {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(text)) {
    return null;
  }

  try {
    return parseDate(text);
  } catch {
    return null;
  }
}

function safeParseTime(text: string): Time | null {
  if (!/^\d{2}:\d{2}(:\d{2})?$/.test(text)) {
    return null;
  }

  try {
    return parseTime(text);
  } catch {
    return null;
  }
}

/**
 * Date + time field in Paris time (F05-T19). The value is a zone-free
 * CalendarDateTime (the wall clock as seen in Europe/Paris); the conversion
 * to an explicit-offset ISO string happens in lib/parisTime.ts, so the sent
 * value never depends on the browser time zone. The native date/time inputs
 * carry wall-clock strings, which are zone-free by nature.
 */
export function ParisDateTimeField({
  label,
  value,
  onChange,
  description,
  resolvedLabel,
  isInvalid = false,
  errorMessage,
  isDisabled = false,
}: ParisDateTimeFieldProps) {
  // Raw input strings, so a half-filled field keeps what was typed
  const [dateText, setDateText] = useState(() => toDateInputValue(value));
  const [timeText, setTimeText] = useState(() => toTimeInputValue(value));
  const descriptionId = useId();
  const errorId = useId();
  // Last value emitted by this field: the sync effect ignores it, so clearing
  // one input never wipes the other one
  const lastEmittedRef = useRef<CalendarDateTime | null>(value);

  // Sync the raw inputs when the value is set from outside (edit form load)
  useEffect(() => {
    if (value === lastEmittedRef.current) {
      return;
    }

    lastEmittedRef.current = value;
    setDateText(toDateInputValue(value));
    setTimeText(toTimeInputValue(value));
  }, [value]);

  function emit(nextDateText: string, nextTimeText: string) {
    const date = safeParseDate(nextDateText);
    const time = safeParseTime(nextTimeText);
    const next =
      date && time
        ? new CalendarDateTime(
            date.year,
            date.month,
            date.day,
            time.hour,
            time.minute,
          )
        : null;

    lastEmittedRef.current = next;
    onChange(next);
  }

  function handleDateChange(event: ChangeEvent<HTMLInputElement>) {
    const text = event.target.value;
    setDateText(text);
    emit(text, timeText);
  }

  function handleTimeChange(event: ChangeEvent<HTMLInputElement>) {
    const text = event.target.value;
    setTimeText(text);
    emit(dateText, text);
  }

  const describedBy =
    [
      description ? descriptionId : null,
      isInvalid && errorMessage ? errorId : null,
    ]
      .filter(Boolean)
      .join(" ") || undefined;

  return (
    <fieldset className="max-w-md rounded-lg border border-border px-4 py-3">
      <legend className="label px-1">{label}</legend>
      <div className="flex flex-wrap gap-2">
        <Input
          type="date"
          aria-label={`${label} — date`}
          value={dateText}
          onChange={handleDateChange}
          disabled={isDisabled}
          aria-invalid={isInvalid || undefined}
          aria-describedby={describedBy}
        />
        <Input
          type="time"
          step={1800}
          aria-label={`${label} — heure`}
          value={timeText}
          onChange={handleTimeChange}
          disabled={isDisabled}
          aria-invalid={isInvalid || undefined}
          aria-describedby={describedBy}
        />
      </div>
      {description ? (
        <p className="description" id={descriptionId}>
          {description}
        </p>
      ) : null}
      {resolvedLabel ? (
        <p className="description">Heure retenue : {resolvedLabel}</p>
      ) : null}
      {isInvalid && errorMessage ? (
        <p className="field-error" role="alert" id={errorId}>
          {errorMessage}
        </p>
      ) : null}
    </fieldset>
  );
}
