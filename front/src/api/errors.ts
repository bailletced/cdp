/**
 * Gestion des erreurs API pour F00-T26.
 * Convertit les erreurs HTTP en messages adaptés pour l'UI.
 */

import { z } from "zod";

// Schéma pour les erreurs de validation (422)
export const ValidationErrorSchema = z.object({
  violations: z.array(
    z.object({
      propertyPath: z.string(),
      message: z.string(),
      code: z.string().optional(),
    }),
  ),
  code: z.string().optional(),
});

// Schéma pour les erreurs de conflit (409)
export const ConflictErrorSchema = z.object({
  message: z.string(),
  code: z.literal("conflict").optional(),
});

// Schéma pour les erreurs d'accès refusé (403)
export const AccessDeniedErrorSchema = z.object({
  message: z.string(),
  code: z.literal("access_denied").optional(),
});

// Schéma pour les erreurs Too Many Requests (429)
export const RateLimitErrorSchema = z.object({
  message: z.string(),
  code: z.literal("rate_limit").optional(),
  retryAfter: z.number().optional(),
});

// Type pour les erreurs API
export interface ApiError {
  message?: string;
  code?: string;
  violations?: Array<{
    propertyPath: string;
    message: string;
    code?: string;
  }>;
  retryAfter?: number;
}

/**
 * Convertit une réponse HTTP en erreur typée.
 * Gère les codes 422, 409, 403, 429.
 */
export async function parseApiError(response: Response): Promise<ApiError> {
  const status = response.status;

  // 422 : Erreurs de validation
  if (status === 422) {
    return ValidationErrorSchema.parseAsync(await response.json())
      .then((data) => data)
      .catch(() => ({
        message: "Erreur de validation",
        violations: [],
      }));
  }

  // 409 : Conflit
  if (status === 409) {
    return ConflictErrorSchema.parseAsync(await response.json())
      .then((data) => data)
      .catch(() => ({
        message: "Conflit : la ressource existe déjà ou est verrouillée.",
        code: "conflict" as const,
      }));
  }

  // 403 : Accès refusé
  if (status === 403) {
    return AccessDeniedErrorSchema.parseAsync(await response.json())
      .then((data) => data)
      .catch(() => ({
        message: "Accès refusé.",
        code: "access_denied" as const,
      }));
  }

  // 429 : Too Many Requests
  if (status === 429) {
    const retryAfter = response.headers.get("Retry-After");
    return RateLimitErrorSchema.parseAsync(await response.json())
      .then((data) => ({
        ...data,
        retryAfter: retryAfter ? Number.parseInt(retryAfter, 10) : undefined,
      }))
      .catch(() => ({
        message: "Trop de requêtes. Veuillez réessayer plus tard.",
        code: "rate_limit" as const,
        retryAfter: retryAfter ? Number.parseInt(retryAfter, 10) : undefined,
      }));
  }

  // Autres erreurs
  return response
    .json()
    .then((data) => data as ApiError)
    .catch(() => ({
      message: `Erreur HTTP ${status}`,
      code: "unknown",
    }));
}

/**
 * Convertit une erreur de validation (422) en erreurs de champ pour TanStack Form.
 * @returns Record<fieldPath, errorMessage>
 */
export function getFieldErrors(error: ApiError): Record<string, string> {
  if (!error.violations) {
    return {};
  }

  const fieldErrors: Record<string, string> = {};

  for (const violation of error.violations) {
    fieldErrors[violation.propertyPath] = violation.message;
  }

  return fieldErrors;
}

/**
 * Vérifie si une erreur est une erreur de validation.
 */
export function isValidationError(
  error: ApiError,
): error is z.infer<typeof ValidationErrorSchema> {
  return "violations" in error;
}

/**
 * Vérifie si une erreur est un conflit.
 */
export function isConflictError(
  error: ApiError,
): error is z.infer<typeof ConflictErrorSchema> {
  return error.code === "conflict";
}

/**
 * Vérifie si une erreur est un accès refusé.
 */
export function isAccessDeniedError(
  error: ApiError,
): error is z.infer<typeof AccessDeniedErrorSchema> {
  return error.code === "access_denied";
}

/**
 * Vérifie si une erreur est une limite de taux.
 */
export function isRateLimitError(
  error: ApiError,
): error is z.infer<typeof RateLimitErrorSchema> {
  return error.code === "rate_limit";
}

/**
 * Extrait le détail lisible d'un corps d'erreur API Platform
 * (Error.jsonld : { title, detail, status }) tel que levé par openapi-fetch.
 */
export function getApiErrorDetail(error: unknown): string | null {
  if (
    typeof error === "object" &&
    error !== null &&
    "detail" in error &&
    typeof error.detail === "string"
  ) {
    return error.detail;
  }

  return null;
}

/**
 * Obtient le message d'erreur à afficher à l'utilisateur.
 */
export function getErrorMessage(error: ApiError): string {
  if (isValidationError(error)) {
    return "Veuillez corriger les erreurs dans le formulaire.";
  }

  if (isConflictError(error)) {
    return (
      error.message || "Conflit : la ressource existe déjà ou est verrouillée."
    );
  }

  if (isAccessDeniedError(error)) {
    return error.message || "Accès refusé.";
  }

  if (isRateLimitError(error)) {
    if (error.retryAfter) {
      return `${error.message ?? "Trop de requêtes."} dans ${error.retryAfter} secondes.`;
    }
    return error.message || "Trop de requêtes. Veuillez réessayer plus tard.";
  }

  return error.message || "Une erreur est survenue.";
}
