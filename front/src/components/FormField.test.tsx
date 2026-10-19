import { type AnyFormApi, useForm } from "@tanstack/react-form";
import { act, fireEvent, render, screen } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { describe, expect, it } from "vitest";
import { baseUrl } from "../api/client";
import { type ApiError, parseApiError } from "../api/errors";
import { server } from "../test/setup";
import { FormField, applyApiErrors } from "./FormField";

// Harness: a single TanStack Form field, exposing the form api to the test.
function SingleFieldForm({
  description,
  onForm,
}: {
  description?: string;
  onForm?: (form: AnyFormApi) => void;
}) {
  const form = useForm({ defaultValues: { email: "" } });
  onForm?.(form);
  return (
    <FormField
      form={form}
      name="email"
      label="Adresse e-mail"
      type="email"
      description={description}
    />
  );
}

// Harness: a two-field form submitted to the API; a 422 is converted and
// pushed back into the form fields.
function InscriptionForm() {
  const form = useForm({
    defaultValues: { email: "", name: "" },
    onSubmit: async ({ value }) => {
      const response = await fetch(`${baseUrl}/api/inscription`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(value),
      });
      if (!response.ok) {
        applyApiErrors(form, await parseApiError(response));
      }
    },
  });

  return (
    <form
      onSubmit={(event) => {
        event.preventDefault();
        void form.handleSubmit();
      }}
    >
      <FormField
        form={form}
        name="email"
        label="Adresse e-mail"
        type="email"
        description="Un lien de connexion vous sera envoyé."
      />
      <FormField form={form} name="name" label="Nom" />
      <button type="submit">S&apos;inscrire</button>
    </form>
  );
}

describe("FormField (F01-T09)", () => {
  it("associates the label with the input", () => {
    render(<SingleFieldForm />);

    expect(screen.getByLabelText("Adresse e-mail")).toBeInTheDocument();
  });

  it("links the help text to the input with aria-describedby", () => {
    render(
      <SingleFieldForm description="Un lien de connexion vous sera envoyé." />,
    );

    const input = screen.getByLabelText("Adresse e-mail");
    const description = screen.getByText(
      "Un lien de connexion vous sera envoyé.",
    );
    expect(input.getAttribute("aria-describedby") ?? "").toContain(
      description.id,
    );
  });

  it("shows the field error and marks the input invalid", async () => {
    let formApi: AnyFormApi | undefined;
    render(
      <SingleFieldForm
        onForm={(form) => {
          formApi = form;
        }}
      />,
    );

    const error: ApiError = {
      violations: [
        { propertyPath: "email", message: "Adresse e-mail invalide." },
      ],
    };
    act(() => {
      if (formApi) {
        applyApiErrors(formApi, error);
      }
    });

    const errorMessage = await screen.findByText("Adresse e-mail invalide.");
    const input = screen.getByLabelText("Adresse e-mail");
    expect(input).toHaveAttribute("aria-invalid", "true");
    expect(input.getAttribute("aria-describedby") ?? "").toContain(
      errorMessage.id,
    );
  });

  it("displays the converted 422 errors on the right field (acceptance)", async () => {
    server.use(
      http.post("http://localhost:8080/api/inscription", () => {
        return HttpResponse.json(
          {
            violations: [
              {
                propertyPath: "email",
                message: "Cette adresse e-mail est invalide.",
              },
            ],
          },
          { status: 422 },
        );
      }),
    );

    render(<InscriptionForm />);

    // A syntactically valid address: jsdom blocks the submit event when the
    // HTML5 constraint validation fails; the 422 violation comes from the API.
    fireEvent.change(screen.getByLabelText("Adresse e-mail"), {
      target: { value: "invalide@example.com" },
    });
    fireEvent.change(screen.getByLabelText("Nom"), {
      target: { value: "Dupont" },
    });
    fireEvent.click(screen.getByRole("button", { name: "S'inscrire" }));

    // The violation lands on the email field, announced and linked to it
    const error = await screen.findByText("Cette adresse e-mail est invalide.");
    const emailInput = screen.getByLabelText("Adresse e-mail");
    expect(emailInput).toHaveAttribute("aria-invalid", "true");
    expect(emailInput.getAttribute("aria-describedby") ?? "").toContain(
      error.id,
    );

    // The other field is untouched
    const nameInput = screen.getByLabelText("Nom");
    expect(nameInput).not.toHaveAttribute("aria-invalid", "true");
  });
});
