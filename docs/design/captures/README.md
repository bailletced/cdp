# Captures de revue du design (F01)

Captures pleine page de la revue de cohérence du thème carmélitain, réalisées
le 17 octobre 2026 (branche `main`, commit `ca5c073`) avec Chromium :

| Fichier | Page | Route |
|---|---|---|
| `home-404-*` | Page 404 provisoire affichée à la racine | `/` |
| `receive-my-link-*` | Formulaire de demande de lien personnel | `/receive-my-link` |
| `confidentialite-*` | Politique de confidentialité (contenu provisoire) | `/confidentialite` |
| `404-*` | Page « introuvable » | `/chemin-inconnu` |
| `demo-*` | Bibliothèque des composants thémés (F01-T05) | `/demo` |

Chaque page existe en deux largeurs : `mobile-390` (390 px) et
`desktop-1280` (1280 px).

La revue accompagnait des contrôles programmatiques (texte ≥ 18 px, cibles
≥ 44 × 44 px, pas de débordement horizontal, jetons du thème) : voir
[../accessibilite.md](../accessibilite.md). Elle a notamment conduit à
l'adoption des composants HeroUI sur `/receive-my-link` (commit `a2a0b0e`).
