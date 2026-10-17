// Config Probity déterministe (aucune IA) pour tester l'adaptateur Vibe de bout en bout.
import { defineConfig, forbidCommandPattern, forbidContentPattern } from '@nizos/probity'

export default defineConfig({
  rules: [
    forbidContentPattern({ match: 'FORBIDDEN', reason: 'contenu interdit' }),
    forbidCommandPattern({ match: 'rm -rf', reason: 'commande destructive interdite' }),
  ],
})
