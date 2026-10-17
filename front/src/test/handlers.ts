import { http, HttpResponse } from 'msw';
import { setupWorker } from 'msw';

// Handlers MSW pour les tests
export const handlers = [
  // Handler pour GET /api/test
  http.get('http://localhost:8080/api/test', () => {
    return HttpResponse.json({ message: 'Test successful' });
  }),
  
  // Handler pour POST /api/test
  http.post('http://localhost:8080/api/test', async ({ request }) => {
    const body = await request.json();
    return HttpResponse.json({ echo: body });
  }),
];

// Setup worker pour le navigateur
export const worker = setupWorker(...handlers);
