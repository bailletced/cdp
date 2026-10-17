# F06 — Bibliothèque de textes

| Lot | Priorité | Estimation |
|---|---|---|
| 1 — Programme | MVP | <!-- est -->16 tâches · 8 h<!-- /est --> |

## Objectif et valeur

Constituer une **bibliothèque de textes à méditer**, commune à toutes les éditions : titre, source, corps mis en forme simplement (paragraphes, gras, italique, citations, retours à la ligne pour les psaumes) et pistes de méditation.

Valeur : les organisateurs saisissent les 48 textes **avant** l'ouverture des inscriptions (c'est un chantier éditorial long, sur le chemin critique) et les réutilisent d'une année sur l'autre. Le contenu riche est nettoyé côté serveur, ce qui protège les priants de tout contenu dangereux.

## Personas concernés

- **Anne, organisatrice** : saisit et relit les textes, souvent par copier-coller depuis un document.
- **Marie, priante** (indirectement) : lit un texte bien mis en forme, lisible.

## User stories

### US-06.1 — Créer un texte
En tant qu'**organisatrice**, je veux saisir un texte (titre, source, corps mis en forme, pistes de méditation facultatives) afin de l'attribuer ensuite à un créneau.

- **Étant donné** un corps collé depuis un traitement de texte, **quand** j'enregistre, **alors** seuls paragraphes, gras, italique, citations et retours à la ligne sont conservés, sans aucun attribut.
- **Étant donné** un corps vide une fois nettoyé, **quand** j'enregistre, **alors** une erreur m'indique que le corps est obligatoire.
- **Étant donné** un titre ou une source manquants, **quand** j'enregistre, **alors** l'erreur s'affiche sur le champ concerné.

### US-06.2 — Modifier un texte
En tant qu'**organisatrice**, je veux corriger un texte afin que la correction s'applique partout où il est attribué (RG-TX-3).

- **Étant donné** un texte attribué à trois créneaux, **quand** je corrige une coquille, **alors** les trois créneaux affichent la version corrigée.

### US-06.3 — Supprimer un texte
En tant qu'**organisatrice**, je veux supprimer un texte inutile, sans casser une édition publiée.

- **Étant donné** un texte attribué à une édition programmée (ou ouverte, en cours, terminée), **quand** je le supprime, **alors** la suppression est refusée avec un message explicite.
- **Étant donné** un texte attribué uniquement à un brouillon, **quand** je le supprime, **alors** il est supprimé et les créneaux concernés n'ont plus de texte.

### US-06.4 — Retrouver un texte
En tant qu'**organisatrice**, je veux lister et rechercher les textes (titre, source) avec leur nombre d'attributions afin de m'y retrouver dans une bibliothèque qui grandit.

- **Étant donné** 120 textes, **quand** je tape « Thérèse », **alors** seuls les textes dont le titre ou la source contiennent ce mot s'affichent.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : §5.6 « Bibliothèque » (fiche d'un texte), RG-TX-3.
- **Décisions techniques** : T27 (Tiptap 3 limité, HtmlSanitizer, VO `RichText`) ; §10.1.

## Dépendances

- F00, F04 (back-office), F05 (créneaux, pour la règle de suppression).
- **Contenu** : les 48 textes de la première édition doivent être fournis par la Communion (point ouvert n° 8 des specs fonctionnelles).

## Hors périmètre

- Attribution aux créneaux (→ F07) et lecture par les priants (→ F12).
- Import en masse (Word, CSV) : saisie unitaire seulement.
- Historique des versions d'un texte.

## Estimation

<!-- est -->**16 tâches × 0,5 h = 8 h** (10 `back`, 5 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
