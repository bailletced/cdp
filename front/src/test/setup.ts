import { afterEach, beforeAll, afterAll } from 'vitest';
import { setupServer } from 'msw/node';
import { handlers } from './handlers';

// Setup MSW server
const server = setupServer(...handlers);

beforeAll(() => {
  server.listen({ onUnhandledRequest: 'error' });
});

afterEach(() => {
  server.resetHandlers();
});

afterAll(() => {
  server.close();
});
