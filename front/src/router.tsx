import { createRouter } from '@tanstack/react-router';
import { Root } from './components/Root';

// Définir les routes
const routeTree = Root.addChildren([]);

export const router = createRouter({
  routeTree,
});

// Déclarer les types pour TypeScript
declare module '@tanstack/react-router' {
  interface Register {
    router: typeof router;
  }
}
