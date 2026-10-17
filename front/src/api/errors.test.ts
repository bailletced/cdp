import { describe, expect, it, beforeEach } from 'vitest';
import {
  parseApiError,
  getFieldErrors,
  getErrorMessage,
  isValidationError,
  isConflictError,
  isAccessDeniedError,
  isRateLimitError,
} from './errors';

describe('API Errors', () => {
  describe('parseApiError', () => {
    it('parses 422 validation errors', async () => {
      const response = new Response(JSON.stringify({
        violations: [
          { propertyPath: 'email', message: 'Adresse e-mail invalide', code: 'INVALID_EMAIL' },
          { propertyPath: 'name', message: 'Le nom est requis', code: 'NOT_BLANK' },
        ],
      }), {
        status: 422,
        headers: { 'Content-Type': 'application/json' },
      });

      const error = await parseApiError(response);
      expect(isValidationError(error)).toBe(true);
      expect(error.violations).toHaveLength(2);
      expect(error.violations[0].propertyPath).toBe('email');
    });

    it('parses 409 conflict errors', async () => {
      const response = new Response(JSON.stringify({
        message: 'Cette adresse e-mail est déjà utilisée.',
        code: 'conflict',
      }), {
        status: 409,
        headers: { 'Content-Type': 'application/json' },
      });

      const error = await parseApiError(response);
      expect(isConflictError(error)).toBe(true);
      expect(error.message).toBe('Cette adresse e-mail est déjà utilisée.');
    });

    it('parses 403 access denied errors', async () => {
      const response = new Response(JSON.stringify({
        message: 'Accès refusé. Vous devez être connecté.',
        code: 'access_denied',
      }), {
        status: 403,
        headers: { 'Content-Type': 'application/json' },
      });

      const error = await parseApiError(response);
      expect(isAccessDeniedError(error)).toBe(true);
      expect(error.message).toBe('Accès refusé. Vous devez être connecté.');
    });

    it('parses 429 rate limit errors', async () => {
      const response = new Response(JSON.stringify({
        message: 'Trop de requêtes.',
        code: 'rate_limit',
      }), {
        status: 429,
        headers: {
          'Content-Type': 'application/json',
          'Retry-After': '60',
        },
      });

      const error = await parseApiError(response);
      expect(isRateLimitError(error)).toBe(true);
      expect(error.message).toBe('Trop de requêtes.');
      expect(error.retryAfter).toBe(60);
    });

    it('parses generic errors', async () => {
      const response = new Response(JSON.stringify({
        message: 'Erreur interne du serveur.',
        code: 'server_error',
      }), {
        status: 500,
        headers: { 'Content-Type': 'application/json' },
      });

      const error = await parseApiError(response);
      expect(error.message).toBe('Erreur interne du serveur.');
    });
  });

  describe('getFieldErrors', () => {
    it('returns empty object for non-validation errors', () => {
      const error = { message: 'Conflict', code: 'conflict' };
      expect(getFieldErrors(error)).toEqual({});
    });

    it('returns field errors for validation errors', () => {
      const error = {
        violations: [
          { propertyPath: 'email', message: 'Adresse e-mail invalide' },
          { propertyPath: 'name', message: 'Le nom est requis' },
        ],
      };
      
      expect(getFieldErrors(error)).toEqual({
        email: 'Adresse e-mail invalide',
        name: 'Le nom est requis',
      });
    });
  });

  describe('getErrorMessage', () => {
    it('returns validation error message', () => {
      const error = {
        violations: [{ propertyPath: 'email', message: 'Invalid' }],
      };
      expect(getErrorMessage(error)).toBe('Veuillez corriger les erreurs dans le formulaire.');
    });

    it('returns conflict error message', () => {
      const error = { message: 'Duplicate email', code: 'conflict' };
      expect(getErrorMessage(error)).toBe('Duplicate email');
    });

    it('returns access denied error message', () => {
      const error = { message: 'Access denied', code: 'access_denied' };
      expect(getErrorMessage(error)).toBe('Access denied');
    });

    it('returns rate limit error message with retryAfter', () => {
      const error = { message: 'Rate limited', code: 'rate_limit', retryAfter: 30 };
      expect(getErrorMessage(error)).toBe('Rate limited dans 30 secondes.');
    });

    it('returns default message for unknown errors', () => {
      const error = { message: 'Something went wrong' };
      expect(getErrorMessage(error)).toBe('Something went wrong');
    });
  });

  describe('error type guards', () => {
    it('identifies validation errors', () => {
      const error = { violations: [] };
      expect(isValidationError(error)).toBe(true);
    });

    it('identifies conflict errors', () => {
      const error = { message: 'Conflict', code: 'conflict' };
      expect(isConflictError(error)).toBe(true);
      expect(isValidationError(error)).toBe(false);
    });

    it('identifies access denied errors', () => {
      const error = { message: 'Access denied', code: 'access_denied' };
      expect(isAccessDeniedError(error)).toBe(true);
    });

    it('identifies rate limit errors', () => {
      const error = { message: 'Rate limited', code: 'rate_limit' };
      expect(isRateLimitError(error)).toBe(true);
    });
  });
});
