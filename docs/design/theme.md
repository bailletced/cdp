# Thème « carmélitain » — brouillon à valider par la Communion

> **F01-T01 / F01-T02** — Brouillon rédigé par Claude, à valider par la Communion (arbitrage Q3).
> Statut : **brouillon**. Toute paire mesurée ci-dessous atteint le niveau **AA** des WCAG 2.1
> (4,5:1 pour le texte normal, 3:1 pour les grands textes et les composants d'interface).

## Identité

Sobre, **brun et crème, beaucoup de blanc** — l'esprit du Carmel : un marron chaud de bure,
un crème de parchemin, et du blanc pour respirer. Jamais d'information portée par la seule
couleur (une icône ou un libellé accompagne toujours un code couleur).

## Palette

| Jeton | Valeur | Rôle |
|---|---|---|
| `background` | `#FFFFFF` | Fond des pages (beaucoup de blanc) |
| `surface` | `#FAF5EC` | Crème : cartes, bandeaux, états alternatifs |
| `text` | `#2B2118` | Texte courant (brun très foncé, quasi noir) |
| `primary` | `#6B4226` | Marron carmélite : liens, titres d'action, bouton principal rempli |
| `primary-dark` | `#54331B` | Marron foncé : survol / appui du bouton principal |
| `muted` | `#6E6259` | « À pourvoir », aide contextuelle, méta-informations |
| `line` | `#8C7B63` | Bordures et séparateurs |
| `success` | `#1E6B34` | Succès (texte + bouton rempli) |
| `danger` | `#B3261E` | Erreur, danger (texte + bouton rempli) |
| `focus` | `#6B4226` | Indicateur de focus (identique au primaire, contour 3 px) |

## Mesures de contraste (WCAG 2.1, niveau AA)

Seuils : **4,5:1** texte normal · **3:1** grands textes (≥ 24 px ou ≥ 18,66 px gras) et
composants d'interface (WCAG 1.4.11).

| Paire mesurée | Sur `background` #FFFFFF | Sur `surface` #FAF5EC | Niveau |
|---|---:|---:|---|
| `text` #2B2118 (texte courant) | **15,75:1** | **14,50:1** | AAA |
| `primary` #6B4226 (texte, lien) | **8,63:1** | **7,95:1** | AAA |
| Blanc #FFFFFF sur `primary` (bouton rempli) | **8,63:1** | — | AAA |
| Blanc #FFFFFF sur `primary-dark` (appui) | **11,25:1** | — | AAA |
| `muted` #6E6259 (« à pourvoir », aide) | **5,91:1** | **5,44:1** | AA |
| `success` #1E6B34 (texte) | **6,55:1** | **6,03:1** | AA |
| Blanc #FFFFFF sur `success` (bouton rempli) | **6,55:1** | — | AA |
| `danger` #B3261E (texte) | **6,54:1** | **6,02:1** | AA |
| Blanc #FFFFFF sur `danger` (bouton rempli) | **6,54:1** | — | AA |
| `line` #8C7B63 (bordures, WCAG 1.4.11) | **4,09:1** | **3,77:1** | ≥ 3:1 |
| `focus` #6B4226 (contour focus, WCAG 1.4.11) | **8,63:1** | **7,95:1** | ≥ 3:1 |

Notes :

- Les placeholders « à pourvoir » passent 4,5:1 : un champ vide reste lisible sans être
  confondu avec une valeur saisie.
- Le contour de focus est le primaire en **3 px** (décalage 2 px) : il dépasse 3:1 sur les deux
  fonds et reste visible quel que soit l'élément focalisé.
- Un état « désactivé » n'est jamais porteur d'information essentielle : le libellé reste
  lisible (assombri mais ≥ 4,5:1) ou l'action est retirée.

## Typographie (F01-T02)

### Police

**Atkinson Hyperlegible** (Braille Institute, licence OFL 1.1) : conçue pour la basse vision,
formes très distinctes (pas de confusion `l`/`1`/`I`, `O`/`0`). **Auto-hébergée** : les fichiers
`woff2` sont servis par le front (`front/public/fonts/`), aucune requête externe — compatible
avec une CSP `font-src 'self'`.

Graisses utilisées : **400** (courant) et **700** (titres, boutons). Pas de 300 : trop fine
pour un public âgé.

### Échelle (mobile d'abord, 360 px)

| Niveau | Taille | Interlignage | Graisse | Usage |
|---|---:|---:|---|---|
| Corps | **18 px** (1,125 rem) | 1,6 | 400 | Tout texte courant — **jamais sous 18 px** (US-01.2) |
| Titre h3 | **20 px** (1,25 rem) | 1,4 | 700 | Sous-sections |
| Titre h2 | **24 px** (1,5 rem) | 1,3 | 700 | Titres de page secondaires |
| Titre h1 | **32 px** (2 rem) | 1,25 | 700 | Titre principal de chaque page (unique, recevant le focus) |

- **Petits textes** (légendes, méta) : jamais réduits en dessous de 18 px — on différencie
  par la couleur `muted`, pas par la taille.
- **Interlignage** : 1,6 pour le corps (confort de lecture, WCAG 1.4.12 en cas de zoom).
- **Longueur de ligne** : maximum ~ 70 caractères (`max-width: 70ch` sur les blocs de texte).
- **Boutons et champs** : libellé en 18 px, 700 pour les actions principales.

### Rayons

Sobres et chaleureux : `8 px` (boutons, champs), `12 px` (cartes, bandeaux). Pas de pillule
complète, pas d'angle vif.

### Implémentation

- Police : `@font-face` dans `front/src/index.css`, fichiers dans `front/public/fonts/`
  (400, 700 + licence OFL).
- Échelle et rayons : thèmes HeroUI surchargés dans `front/src/index.css`
  (corps 18 px, `rounded-lg` / `rounded-xl`, contour de focus primaire 3 px).
