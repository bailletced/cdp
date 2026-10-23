import { describe, expect, it } from "vitest";
import { z } from "zod";
import { standardSchemaValidator } from "./standardSchema";

const schema = z.object({
  displayName: z.string().min(1, "Le nom affiché est obligatoire."),
  email: z.string().email("L'adresse e-mail n'est pas valide."),
});

describe("standardSchemaValidator", () => {
  it("maps the issues to field error messages keyed by path", () => {
    const validate = standardSchemaValidator(schema);

    expect(
      validate({ value: { displayName: "", email: "not-an-email" } }),
    ).toEqual({
      fields: {
        displayName: ["Le nom affiché est obligatoire."],
        email: ["L'adresse e-mail n'est pas valide."],
      },
    });
  });

  it("returns undefined when the values are valid", () => {
    const validate = standardSchemaValidator(schema);

    expect(
      validate({
        value: {
          displayName: "Famille Martin",
          email: "famille.martin@bailletced.org",
        },
      }),
    ).toBeUndefined();
  });

  it("groups several issues of the same field", () => {
    const validate = standardSchemaValidator(
      z.object({
        email: z.string().min(3, "Trop court.").max(5, "Trop long."),
      }),
    );

    expect(validate({ value: { email: "ab" } })).toEqual({
      fields: { email: ["Trop court."] },
    });
    expect(validate({ value: { email: "abcdef" } })).toEqual({
      fields: { email: ["Trop long."] },
    });
  });
});
