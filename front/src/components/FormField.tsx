import {
  Description,
  FieldError,
  Input,
  Label,
  TextField,
} from "@heroui/react";
import { type AnyFormApi, useField } from "@tanstack/react-form";
import { type ApiError, getFieldErrors } from "../api/errors";

interface FormFieldProps {
  form: AnyFormApi;
  name: string;
  label: string;
  description?: string;
  type?: string;
  placeholder?: string;
  autoComplete?: string;
}

/**
 * Form field (F01-T09): label + input + help text + error, wired to a
 * TanStack Form field. The HeroUI TextField links the description and the
 * error to the input with aria-describedby; the error is announced when the
 * field becomes invalid.
 */
export function FormField({
  form,
  name,
  label,
  description,
  type = "text",
  placeholder,
  autoComplete,
}: FormFieldProps) {
  const field = useField({ form, name });
  const errors = field.state.meta.errors as string[];
  return (
    <TextField isInvalid={errors.length > 0} className="max-w-md">
      <Label>{label}</Label>
      <Input
        name={field.name}
        type={type}
        placeholder={placeholder}
        autoComplete={autoComplete}
        value={String(field.state.value ?? "")}
        onBlur={field.handleBlur}
        onChange={(event) => field.handleChange(event.target.value)}
      />
      {description ? <Description>{description}</Description> : null}
      <FieldError>{errors[0]}</FieldError>
    </TextField>
  );
}

/**
 * Pushes the field errors of an API error (422 converted by api/errors.ts)
 * into the form: each violation lands on its own field. The error goes
 * through errorMap (onServer cause): the form store derives meta.errors
 * from errorMap, so writing errors directly would be overwritten.
 */
export function applyApiErrors(form: AnyFormApi, error: ApiError): void {
  for (const [name, message] of Object.entries(getFieldErrors(error))) {
    form.setFieldMeta(name, (previous) => ({
      ...previous,
      errorMap: { ...previous?.errorMap, onServer: message },
      isTouched: true,
    }));
  }
}
