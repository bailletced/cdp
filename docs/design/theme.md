# Thème carmélitain « la veille » — à valider par la Communion

> **F01-T01 / F01-T02**, refondu le 2026-10-23 à la demande de Cédric : style sombre.
> Remplace le brouillon clair « brun et crème, beaucoup de blanc ». Statut : **à valider par la
> Communion** (arbitrage Q3). Toute paire mesurée ci-dessous atteint le niveau **AA** des WCAG 2.1
> (4,5:1 pour le texte normal, 3:1 pour les grands textes et les composants d'interface).

## Identité

Les 24 heures vues comme une **veillée au Carmel** : le brun de la bure dans la nuit pour le
fond, le blanc du manteau pour le texte, et l'or d'une lampe de sanctuaire comme **seule
lumière**, réservée à ce qui agit ou retient l'attention (action principale, focus, créneau
choisi, icônes). Une seule touche d'atmosphère : la lueur de la lampe en haut de page. Jamais
d'information portée par la seule couleur (une icône ou un libellé accompagne toujours un code
couleur).

## Palette

| Jeton | Valeur | Rôle |
|---|---|---|
| `background` | `#211913` | Fond des pages : la bure dans la nuit |
| `surface` | `#2D231B` | Cartes, bandeaux, barre de sélection |
| `surface-secondary` | `#3A2E25` | Boutons secondaires, surfaces surélevées |
| `foreground` | `#F3ECE0` | Texte courant : le blanc du manteau |
| `muted` | `#B9A78F` | « À pourvoir », aide contextuelle, méta-informations |
| `accent` | `#E6B762` | Or de la lampe : bouton principal rempli, liens, icônes |
| `accent-foreground` | `#211913` | Texte sur l'or |
| `accent-hover` | `#F0C97F` | Survol / appui du bouton principal |
| `border` | `#977F69` | Bordures des champs et composants (porteuses d'information) |
| `separator` | `#4A3B2F` | Filets décoratifs uniquement (aucune information) |
| `field-background` | `#1A140F` | Fond des champs, plus sombre que la page |
| `success` | `#8FCC9A` | Succès (texte + bouton rempli, texte sombre) |
| `warning` | `#E9C46A` | Avertissement |
| `danger` | `#F2958C` | Erreur, danger (texte + bouton rempli, texte sombre) |
| `focus` | `#E6B762` | Indicateur de focus (or, contour 3 px) |

## Mesures de contraste (WCAG 2.1, niveau AA)

Seuils : **4,5:1** texte normal · **3:1** grands textes (≥ 24 px ou ≥ 18,66 px gras) et
composants d'interface (WCAG 1.4.11).

| Paire mesurée | Sur `background` #211913 | Sur `surface` #2D231B | Niveau |
|---|---:|---:|---|
| `foreground` #F3ECE0 (texte courant) | **14,74:1** | **13,08:1** | AAA |
| `muted` #B9A78F (« à pourvoir », aide) | **7,41:1** | **6,57:1** | AAA |
| `accent` #E6B762 (lien, icône) | **9,33:1** | **8,27:1** | AAA |
| `accent-foreground` #211913 sur `accent` (bouton rempli) | **9,33:1** | — | AAA |
| `success` #8FCC9A (texte) | **9,29:1** | **8,24:1** | AAA |
| `warning` #E9C46A (texte) | **10,36:1** | **9,19:1** | AAA |
| `danger` #F2958C (texte) | **7,79:1** | **6,91:1** | AAA |
| `border` #977F69 (bordures, WCAG 1.4.11) | **4,58:1** | **4,06:1** | ≥ 3:1 |
| `focus` #E6B762 (contour focus, WCAG 1.4.11) | **9,33:1** | **8,27:1** | ≥ 3:1 |

Sur `surface-secondary` #3A2E25, `border` reste à **3,48:1** et `muted` à **5,63:1**. Le texte
des champs (`foreground` sur `field-background` #1A140F) est à **15,54:1**.

Notes :

- Les placeholders « à pourvoir » passent 4,5:1 : un champ vide reste lisible sans être
  confondu avec une valeur saisie.
- Le contour de focus est le primaire en **3 px** (décalage 2 px) : il dépasse 3:1 sur les deux
  fonds et reste visible quel que soit l'élément focalisé.
- Un état « désactivé » n'est jamais porteur d'information essentielle : le libellé reste
  lisible (assombri mais ≥ 4,5:1) ou l'action est retirée.

## Typographie (F01-T02)

### Polices

**Alegreya** (Huerta Tipográfica, licence OFL 1.1) pour les **titres** et les horaires des
créneaux : un serif humaniste d'origine calligraphique, qui porte la gravité du sujet sans
raideur. Graisses 500 et 700, chiffres alignés tabulaires sur les horaires. **Auto-hébergée**
comme la police du texte (`front/public/fonts/Alegreya-*.woff2`, licence `OFL-Alegreya.txt`).

**Atkinson Hyperlegible** pour **tout le texte courant** (Braille Institute, licence OFL 1.1) : conçue pour la basse vision,
formes très distinctes (pas de confusion `l`/`1`/`I`, `O`/`0`). **Auto-hébergée** : les fichiers
`woff2` sont servis par le front (`front/public/fonts/`), aucune requête externe — compatible
avec une CSP `font-src 'self'`.

Graisses utilisées : **400** (courant) et **700** (titres, boutons). Pas de 300 : trop fine
pour un public âgé.

### Échelle (mobile d'abord, 360 px)

| Niveau | Taille | Interlignage | Graisse | Usage |
|---|---:|---:|---|---|
| Corps | **18 px** (1,125 rem) | 1,6 | 400 | Tout texte courant — **jamais sous 18 px** (US-01.2) |
| Titre h3 | **24 px** (1,5 rem) | 1,3 | 500 Alegreya | Sous-sections |
| Titre h2 | **30 px** (1,875 rem) | 1,2 | 700 Alegreya | Titres de section (moments de la journée…) |
| Titre h1 | **40 → 60 px** (`clamp`) | 1,08 | 700 Alegreya | Titre principal de chaque page (unique, recevant le focus) |

- **Petits textes** (légendes, méta) : jamais réduits en dessous de 18 px — on différencie
  par la couleur `muted`, pas par la taille.
- **Interlignage** : 1,6 pour le corps (confort de lecture, WCAG 1.4.12 en cas de zoom).
- **Longueur de ligne** : maximum ~ 70 caractères (`max-width: 70ch` sur les blocs de texte).
- **Boutons et champs** : libellé en 18 px, 700 pour les actions principales.

### Rayons

Hiérarchisés : `10 px` (boutons, champs), `16 px` (cartes-créneaux, panneaux, barre de sélection). Pas de pillule
complète, pas d'angle vif.

### Implémentation

- Polices : `@font-face` dans `front/src/index.css`, fichiers dans `front/public/fonts/`
  (Atkinson 400, 700, italique ; Alegreya 500, 700 ; licences OFL). Utilitaire Tailwind
  `font-display` pour Alegreya.
- Palette : jetons HeroUI surchargés dans la couche `base` de `front/src/index.css` (ils
  l'emportent sur la couche `theme` de HeroUI) ; `<html class="dark">` et `color-scheme: dark`.
- Échelle et rayons : corps 18 px, `--radius` 10 px, `rounded-2xl` pour les cartes, contour de
  focus or 3 px.
