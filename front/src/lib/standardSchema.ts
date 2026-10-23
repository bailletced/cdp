import type { StandardSchemaV1 } from "@tanstack/react-form";

/**
 * Adapts a Standard Schema (zod, …) to a TanStack Form validator: the
 * issues are mapped to field error messages keyed by their first path
 * segment — the shape TanStack Form distributes into the fields'
 * meta.errors, which is what FormField displays. The schema is only ever
 * touched through its ~standard interface, so any Standard Schema
 * implementation works.
 */
export function standardSchemaValidator<Input, Output>(
  schema: StandardSchemaV1<Input, Output>,
) {
  return ({ value }: { value: unknown }) => {
    const result = schema["~standard"].validate(value);

    if (result instanceof Promise) {
      throw new Error("Async Standard Schemas are not supported.");
    }

    if (!result.issues) {
      return undefined;
    }

    const fields: Record<string, string[]> = {};

    for (const issue of result.issues) {
      const segment = issue.path?.[0];

      if (typeof segment !== "string") {
        continue;
      }

      const messages = fields[segment] ?? [];
      messages.push(issue.message);
      fields[segment] = messages;
    }

    return { fields };
  };
}
