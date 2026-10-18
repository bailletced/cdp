/**
 * Helpers pour interagir avec Mailpit dans les tests e2e.
 * F00-T27 : Tests Playwright sur la stack compose.
 */

import { request } from "@playwright/test";

export interface MailpitEmail {
  id: string;
  from: string;
  to: string[];
  subject: string;
  body: string;
  timestamp: string;
}

const MAILPIT_API_URL = "http://mailpit:8025/api/v1";

/**
 * Récupère le dernier e-mail envoyé à une adresse donnée.
 */
export async function getLastEmailTo(
  address: string,
): Promise<MailpitEmail | null> {
  const context = await request.newContext();
  const api = context.request;

  try {
    // Récupérer tous les messages
    const response = await api.get(`${MAILPIT_API_URL}/messages`);
    const messages: MailpitEmail[] = await response.json();

    // Filtrer par destinataire et retourner le plus récent
    const filtered = messages.filter((msg) => msg.to.includes(address));

    if (filtered.length === 0) {
      return null;
    }

    // Retourner le plus récent (le dernier dans la liste)
    return filtered[filtered.length - 1];
  } catch (error) {
    console.error("Erreur lors de la récupération des e-mails:", error);
    return null;
  }
}

/**
 * Extrait un lien d'un e-mail.
 */
export function extractLinkFromEmail(
  email: MailpitEmail,
  linkText?: string,
): string | null {
  if (!email.body) {
    return null;
  }

  // Rechercher un lien dans le corps de l'e-mail
  const linkMatch = email.body.match(/https?:\/\/[^\s]+/);

  if (linkMatch) {
    return linkMatch[0];
  }

  // Si un texte de lien est fourni, essayer de le trouver
  if (linkText) {
    const textMatch = email.body.match(
      new RegExp(`${linkText}[\\s\\S]*?(https?:\/\/[^\\s]+)`),
    );
    if (textMatch) {
      return textMatch[1];
    }
  }

  return null;
}

/**
 * Vide tous les messages de Mailpit.
 */
export async function clearMailpit(): Promise<void> {
  const context = await request.newContext();
  const api = context.request;

  try {
    await api.delete(`${MAILPIT_API_URL}/messages`);
  } catch (error) {
    console.error("Erreur lors du vidage de Mailpit:", error);
  }
}

/**
 * Attend qu'un e-mail arrive pour un destinataire.
 */
export async function waitForEmail(
  address: string,
  timeout = 30000,
  pollInterval = 2000,
): Promise<MailpitEmail | null> {
  const startTime = Date.now();

  while (Date.now() - startTime < timeout) {
    const email = await getLastEmailTo(address);
    if (email) {
      return email;
    }
    await new Promise((resolve) => setTimeout(resolve, pollInterval));
  }

  return null;
}
