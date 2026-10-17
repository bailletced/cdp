# F01 — Thème carmélitain et accessibilité de base

| Lot | Priorité | Estimation |
|---|---|---|
| 0 — Socle | MVP | <!-- est -->13 tâches · 6,5 h<!-- /est --> |

## Objectif et valeur

Donner à l'application son identité « carmélitaine » (sobre, brun et crème, beaucoup de blanc) et, surtout, un socle d'interface **accessible par construction** pour un public en partie âgé : corps ≥ 18 px, zones tactiles ≥ 44 px, contrastes AA, information jamais portée par la seule couleur, gestion du focus et des annonces.

Faire ce travail **avant** les écrans métier évite de reprendre chaque écran plus tard ; le contrôle axe-core bloquant en CI empêche toute régression.

## Personas concernés

- **Marie, 74 ans, priante fidèle** : lit sur un téléphone, a besoin de gros caractères et de boutons larges.
- **Claire, 29 ans, visiteuse** : découvre la Communion, doit percevoir une identité soignée et sobre.
- **Développeur** : dispose de composants partagés qui respectent les règles sans effort.

## User stories

### US-01.1 — Identité visuelle carmélitaine
En tant que **responsable de la Communion**, je veux valider une palette et une typographie proposées en brouillon par Claude afin que l'application reflète l'esprit du Carmel.

- **Étant donné** la palette proposée, **quand** chaque paire texte/fond est mesurée, **alors** toutes atteignent le niveau AA (4,5:1, ou 3:1 pour les grands textes et composants).

### US-01.2 — Lire et toucher confortablement
En tant que **Marie**, je veux des textes d'au moins 18 px et des boutons d'au moins 44 px afin d'utiliser l'application sans loupe ni erreur de toucher.

- **Étant donné** un téléphone de 360 px de large, **quand** j'affiche une page, **alors** aucun texte courant n'est sous 18 px et chaque cible tactile mesure au moins 44 × 44 px.

### US-01.3 — Naviguer au clavier et au lecteur d'écran
En tant qu'**utilisateur de lecteur d'écran**, je veux des repères (titres, régions, lien d'évitement) et des changements de page annoncés afin de m'orienter.

- **Étant donné** une navigation vers une nouvelle route, **quand** la page s'affiche, **alors** le titre du document change et le focus est placé sur le titre principal.
- **Étant donné** une erreur de saisie, **quand** le formulaire est envoyé, **alors** le message est relié au champ (`aria-describedby`) et annoncé.

### US-01.4 — Contrôle automatique
En tant que **mainteneur**, je veux qu'axe-core bloque la CI à la moindre violation afin de garantir l'accessibilité dans la durée.

- **Étant donné** une page avec une image sans alternative, **quand** l'e2e tourne, **alors** le test échoue.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : exigences non fonctionnelles §6 (public âgé, accessibilité, identité visuelle, langue) ; principe « jamais d'information par la seule couleur » (§5.3-1).
- **Décisions techniques** : T23 (HeroUI, TanStack Form), T32 (axe-core) ; §8.5.

## Dépendances

- F00 (SPA, Vitest, Playwright, `api/errors.ts`).

## Hors périmètre

- Logo ou illustration sur mesure (à fournir par la Communion si souhaité).
- Audit manuel VoiceOver/NVDA et tests utilisateurs (→ F26).
- Contenu définitif de la page « Confidentialité » (→ F25).
- Mode sombre.

## Estimation

<!-- est -->**13 tâches × 0,5 h = 6,5 h** (7 `front`, 1 `test`, 1 `doc`, 4 `design`). Détail dans [taches.md](taches.md).<!-- /est -->
