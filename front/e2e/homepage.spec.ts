import { test, expect } from '@playwright/test';
import { clearMailpit, waitForEmail } from './helpers/mailpit';

test.beforeEach(async () => {
  // Vider Mailpit avant chaque test
  await clearMailpit();
});

test.describe('Page d\'accueil', () => {
  test('répond avec un code 200', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveTitle(/CDP|Chaîne de prière/);
    await expect(page).toHaveStatus(200);
  });

  test('affiche le titre de l\'application', async ({ page }) => {
    await page.goto('/');
    await expect(page.getByRole('heading', { name: /CDP|Chaîne de prière/ })).toBeVisible();
  });

  test('afficher un message par défaut', async ({ page }) => {
    await page.goto('/');
    await expect(page.getByText(/Bienvenue|Chaîne de prière|24 heures/)).toBeVisible();
  });
});

test.describe('API', () => {
  test('l\'API répond sur /api', async ({ page, request }) => {
    // Ce test vérifie que l'API est accessible depuis le frontend
    // En développement, le proxy Vite doit rediriger /api vers http://php:8080
    const response = await request.get('/api/test');
    expect(response.ok()).toBeTruthy();
  });
});

test.describe('Mailpit', () => {
  test('peut recevoir des e-mails', async () => {
    // Ce test vérifie que la connexion à Mailpit fonctionne
    const email = await waitForEmail('test@example.com', 10000);
    // Pas d'assertion, juste vérifier que ça ne lance pas d'erreur
    expect(true).toBe(true);
  });
});
