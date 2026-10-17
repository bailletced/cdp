/**
 * Gestion des erreurs API pour F00-T26.
 * Convertit les erreurs HTTP en messages adaptés pour l'UI.
 */

import { z } from 'zod';

// Schéma pour les erreurs de validation (422)
export const ValidationErrorSchema = z.object({
  violations: z.array(
    z.object({
      propertyPath: z.string(),
      message: z.string(),
      code: z.string().optional(),
    }),
  ),
});

// Schéma pour les erreurs de conflit (409)
export const ConflictErrorSchema = z.object({
  message: z.string(),
  code: z.literal('conflict').optional(),
});

// Schéma pour les erreurs d'accès refusé (403)
export const AccessDeniedErrorSchema = z.object({
  message: z.string(),
  code: z.literal('access_denied').optional(),
});

// Schéma pour les erreurs Too Many Requests (429)
export const RateLimitErrorSchema = z.object({
  message: z.string(),
  code: z.literal('rate_limit').optional(),
  retryAfter: z.number().optional(),
});

// Type pour les erreurs API
export type ApiError = z.infer<typeof ValidationErrorSchema> | 
  z.infer<typeof ConflictErrorSchema> | 
  z.infer<typeof AccessDeniedErrorSchema> | 
  z.infer<typeof RateLimitErrorSchema> | {
    message: string;
    code?: string;
  };

/**
 * Convertit une réponse HTTP en erreur typée.
 * Gère les codes 422, 409, 403, 429.
 */
export function parseApiError(response: Response): ApiError {
  const status = response.status;
  
  // 422 : Erreurs de validation
  if (status === 422) {
    return ValidationErrorSchema.parseAsync(response.json())
      .then((data) => data)
      .catch(() => ({
        message: 'Erreur de validation',
        violations: [],
      })) as Promise<ApiError>;
  }

  // 409 : Conflit
  if (status === 409) {
    return ConflictErrorSchema.parseAsync(response.json())
      .then((data) => data)
      .catch(() => ({
        message: 'Conflit : la ressource existe déjà ou est verrouillée.',
        code: 'conflict',
      })) as Promise<ApiError>;
  }

  // 403 : Accès refusé
  if (status === 403) {
    return AccessDeniedErrorSchema.parseAsync(response.json())
      .then((data) => data)
      .catch(() => ({
        message: 'Accès refusé.',
        code: 'access_denied',
      })) as Promise<ApiError>;
  }

  // 429 : Too Many Requests
  if (status === 429) {
    const retryAfter = response.headers.get('Retry-After');
    return RateLimitErrorSchema.parseAsync(response.json())
      .then((data) => ({
        ...data,
        retryAfter: retryAfter ? parseInt(retryAfter, 10) : undefined,
      }))
      .catch(() => ({
        message: 'Trop de requêtes. Veuillez réessayer plus tard.',
        code: 'rate_limit',
        retryAfter: retryAfter ? parseInt(retryAfter, 10) : undefined,
      })) as Promise<ApiError>;
  }

  // Autres erreurs
  return response.json()
    .then((data) => data as ApiError)
    .catch(() => ({
      message: `Erreur HTTP ${status}`,
      code: 'unknown',
    })) as Promise<ApiError>;
}

/**
 * Convertit une erreur de validation (422) en erreurs de champ pour TanStack Form.
 * @returns Record<fieldPath, errorMessage>
 */
export function getFieldErrors(error: ApiError): Record<string, string> {
  if ('violations' in error) {
    const fieldErrors: Record<string, string> = {};
    
    for (const violation of error.violations) {
      fieldErrors[violation.propertyPath] = violation.message;
    }
    
    return fieldErrors;
  }
  
  return {};
}

/**
 * Vérifie si une erreur est une erreur de validation.
 */
export function isValidationError(error: ApiError): error is z.infer<typeof ValidationErrorSchema> {
  return 'violations' in error;
}

/**
 * Vérifie si une erreur est un conflit.
 */
export function isConflictError(error: ApiError): error is z.infer<typeof ConflictErrorSchema> {
  return error.code === 'conflict';
}

/**
 * Vérifie si une erreur est un accès refusé.
 */
export function isAccessDeniedError(error: ApiError): error is z.infer<typeof AccessDeniedErrorSchema> {
  return error.code === 'access_denied';
}

/**
 * Vérifie si une erreur est une limite de taux.
 */
export function isRateLimitError(error: ApiError): error is z.infer<typeof RateLimitErrorSchema> {
  return error.code === 'rate_limit';
}

/**
 * Obtient le message d'erreur à afficher à l'utilisateur.
 */
export function getErrorMessage(error: ApiError): string {
  if (isValidationError(error)) {
    return 'Veuillez corriger les erreurs dans le formulaire.';
  }
  
  if (isConflictError(error)) {
    return error.message || 'Conflit : la ressource existe déjà ou est verrouillée.';
  }
  
  if (isAccessDeniedError(error)) {
    return error.message || 'Accès refusé.';
  }
  
  if (isRateLimitError(error)) {
    const retryAfter = error.retryAfter ? ` dans ${error.retryAfter} secondes` : '';
    return error.message || `Trop de requêtes. Veuillez réessayer${retryAfter}.`;
  }
  
  return error.message || 'Une erreur est survenue.';
}
