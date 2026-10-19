# Check-list accessibilité développeurs

> **F01-T13** — À respecter pour toute évolution d'interface. Référence de la
> définition de « terminé » (index des features, §5). Les contrôles automatiques :
> `make qa` (Biome, `tsc`), Vitest/MSW, et l'audit axe-core bloquant en e2e
> (`front/e2e/a11y.spec.ts`, helper `expectNoA11yViolations`, F01-T12).

Le socle est posé par F01 : thème « carmélitain » ([theme.md](theme.md)), layout public
avec lien d'évitement et régions (F01-T07), titres de page annonçés (F01-T08), cibles
tactiles ≥ 44 px et focus visible (F01-T06), composants accessibles `FormField` /
`ConfirmDialog` (F01-T09), région `aria-live` et notifications (F01-T10), pages 404 et
erreur (F01-T11).

## Avant de livrer une interface

- [ ] **La couleur ne porte jamais l'information seule** (WCAG 1.4.1, §5.3-1) : un état
      (succès, erreur, « à pourvoir ») s'accompagne d'un libellé ou d'une icône
      explicite. Les liens sont soulignés au repos (`.link`).
- [ ] **Chaque champ a un libellé** (WCAG 1.3.1, 3.3.2) : utiliser le composant
      `FormField` (HeroUI `TextField` + `Label`) — jamais un placeholder comme seul
      libellé. Le libellé est relié à l'input (`label`/`for` géré par React Aria).
- [ ] **Cibles tactiles ≥ 44 × 44 px** (WCAG 2.5.8) : boutons, liens, cases à cocher,
      champs — les surcharges de `index.css` (F01-T06) appliquent 44 px aux composants
      HeroUI ; ne pas les réduire. Un lien dans un paragraphe garde sa hauteur de ligne
      grâce au padding compensé (`.link`).
- [ ] **Focus visible et contrasté** (WCAG 2.4.7, 1.4.11) : contour 3 px `focus`
      (`#6B4226`, 8,63:1) — ne pas supprimer `outline`/`ring`. L'ordre de tabulation suit
      l'ordre visuel (pas de `tabindex` positif).
- [ ] **Ordre de tabulation logique** (WCAG 2.4.3) : la navigation au clavier suit le
      parcours de lecture ; le lien d'évitement (« Aller au contenu ») est le premier
      élément focalisable ; le contenu principal est un repère `main` unique.
- [ ] **Les erreurs de champ sont reliées et annoncées** (WCAG 3.3.1, 3.3.3) : utiliser
      `FormField` + `applyApiErrors` (F01-T09) — le message d'erreur 422 est relié au
      champ par `aria-describedby`, l'input passe `aria-invalid="true"`, et le message
      est annoncé au lecteur d'écran.
- [ ] **Les changements de page sont annoncés** (WCAG 3.2.2) : chaque page utilise
      `PageTitle` (F01-T08) — le titre du document change, le focus est placé sur le
      titre principal (h1), annoncé via `aria-live="polite"`.
- [ ] **Les notifications sont accessibles** (F01-T10) : utiliser `notify()` — région
      `aria-live` (contenu `role="alert"`), fermeture au clavier (bouton « Fermer la
      notification »), durée suffisante (8 s) avec pause au survol et au focus
      (WCAG 2.2.1).
- [ ] **Les dialogues de confirmation** (F01-T09) : utiliser `ConfirmDialog` (React
      Aria `AlertDialog`) — `role="alertdialog"`, focus piégé, Échap annule.
- [ ] **États de chargement / erreur / vide** (F01-T08) : utiliser `LoadingState`
      (`<output>`, spinner `aria-hidden`), `ErrorState` (`role="alert"` + « Réessayer »),
      `EmptyState`.
- [ ] **Texte ≥ 18 px** (US-01.2) : le corps est à 18 px (`index.css`) ; ne pas
      descendre sous 18 px pour du texte courant, y compris dans les composants HeroUI
      (surcharge `[data-slot="input"]` etc.).
- [ ] **Contrastes AA** (WCAG 1.4.3, 1.4.11) : n'utiliser que les paires mesurées de
      [theme.md](theme.md) ; pour toute nouvelle couleur, mesurer le contraste avant
      commit (4,5:1 texte, 3:1 composants).
- [ ] **Pas d'image sans alternative** (WCAG 1.1.1) : `alt` obligatoire ; images
      décoratives en `alt=""` — l'audit axe-core e2e bloque sinon (test négatif F01-T12).
- [ ] **Textes en français, en heure de Paris** ; nom du site depuis `src/config/site.ts`
      (« Communion de prière Notre-Dame du Mont-Carmel »).

## Contrôle

- `make qa` vert (Biome, `tsc`) ; tests Vitest/MSW verts ;
- e2e Playwright verts, dont l'audit axe-core `expectNoA11yViolations` appliqué à `/`,
  `/confidentialite` et la 404 (`front/e2e/a11y.spec.ts`) ;
- revue visuelle sur téléphone (360–390 px) : pas de texte sous 18 px, pas de cible
  sous 44 px, pas de débordement horizontal.
