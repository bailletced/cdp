# F01 — Thème carmélitain et accessibilité de base · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `front` | 7 | 3,5 h |
| `test` | 1 | 0,5 h |
| `doc` | 1 | 0,5 h |
| `design` | 4 | 2 h |
| **Total** | **13** | **6,5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Identité visuelle (US-01.1)

- [ ] **F01-T01** · `design` Brouillon de palette (rédigé par Claude) brun / crème / blanc (tokens primaire, surface, texte, « à pourvoir », succès, danger) et mesure du contraste AA de chaque paire — *Dépend de* : — · *Fini quand* : tableau des contrastes dans `docs/design/theme.md`, validé par la Communion.
- [ ] **F01-T02** · `design` Brouillon de typographie (Claude) : police très lisible auto-hébergée (CSP `self`), échelle (corps 18 px minimum, titres), interlignage — *Dépend de* : F01-T01 · *Fini quand* : échelle documentée dans `docs/design/theme.md`, validée par la Communion.
- [ ] **F01-T03** · `design` Maquettes mobiles basse fidélité : accueil (bandeau, grille), formulaire d'inscription, confirmation — *Dépend de* : F01-T01 · *Fini quand* : maquettes relues par un organisateur.
- [ ] **F01-T04** · `design` Maquettes mobiles : Mon espace (dont « pendant mon créneau »), lecture d'un texte, bloc « En ce moment » — *Dépend de* : F01-T03 · *Fini quand* : maquettes relues par un organisateur.

## Thème et composants (US-01.2, US-01.3)

- [ ] **F01-T05** · `front` HeroUI + Tailwind CSS 4 : thème « carmélitain » (tokens, police, rayons), corps de texte 18 px — *Dépend de* : F01-T02, F00-T22 · *Fini quand* : page de démonstration des composants thémés.
- [ ] **F01-T06** · `front` Surcharge des tailles HeroUI : cibles ≥ 44 px (Button, Checkbox, Input, Link), focus visible contrasté — *Dépend de* : F01-T05 · *Fini quand* : test Vitest sur les variantes, contrôle visuel sur téléphone.
- [ ] **F01-T07** · `front` Layout public : en-tête (« Communion de prière Notre-Dame du Mont-Carmel », depuis une configuration unique), pied de page (Confidentialité, Recevoir mon lien), lien d'évitement, régions — *Dépend de* : F01-T06 · *Fini quand* : test de rendu, structure de titres correcte.
- [ ] **F01-T08** · `front` `PageTitle` (titre du document, focus et annonce au changement de route), `LoadingState`, `ErrorState`, `EmptyState` + tests — *Dépend de* : F01-T07 · *Fini quand* : tests Testing Library sur focus et annonce.
- [x] **F01-T09** · `front` `FormField` (libellé, aide, erreur reliée par `aria-describedby`, intégration TanStack Form) et `ConfirmDialog` (React Aria) + tests — *Dépend de* : F01-T06, F00-T26 · *Fini quand* : erreurs 422 converties s'affichent sur le bon champ dans un test.
- [x] **F01-T10** · `front` Région `aria-live` et notifications de confirmation accessibles (fermeture clavier, durée suffisante) + tests — *Dépend de* : F01-T07 · *Fini quand* : un message est annoncé dans un test.
- [x] **F01-T11** · `front` Pages 404 et erreur globale (`errorComponent`), route `/confidentialite` au contenu provisoire — *Dépend de* : F01-T08 · *Fini quand* : les trois pages s'affichent dans le layout.

## Contrôles (US-01.4)

- [x] **F01-T12** · `test` `@axe-core/playwright` : helper `expectNoA11yViolations` bloquant, appliqué à `/`, `/confidentialite` et à la 404 — *Dépend de* : F01-T11, F00-T27 · *Fini quand* : une violation volontaire fait échouer l'e2e.
- [x] **F01-T13** · `doc` Check-list accessibilité des développeurs (couleur jamais seule, libellés, 44 px, ordre de tabulation, messages d'erreur) dans `docs/design/accessibilite.md` — *Dépend de* : F01-T06 · *Fini quand* : référencée dans la définition de « terminé ».
