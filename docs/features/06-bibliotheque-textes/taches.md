# F06 — Bibliothèque de textes · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 10 | 5 h |
| `front` | 5 | 2,5 h |
| `test` | 1 | 0,5 h |
| **Total** | **16** | **8 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Domaine et persistance (US-06.1, US-06.2)

- [x] **F06-T01** · `back` Port `HtmlSanitizer` (Chain/Application/Port) + adaptateur Symfony HtmlSanitizer (`p`, `br`, `strong`, `em`, `blockquote`, aucun attribut) + tests (XSS, attributs, balises interdites) — *Dépend de* : F00-T09 · *Fini quand* : jeu de 10 entrées malveillantes nettoyé (tests).
- [x] **F06-T02** · `back` VO `RichText` (HTML déjà nettoyé, non vide une fois les balises retirées, longueur max), `TextId`, `TextTitle`, `TextSource` + tests unitaires — *Dépend de* : F00-T12 · *Fini quand* : bornes et cas vides testés.
- [x] **F06-T03** · `back` Agrégat `Text` (titre, source, corps, pistes facultatives) + `TextRepositoryInterface` + InMemory + suite abstraite — *Dépend de* : F06-T02 · *Fini quand* : suite verte sur InMemory.
- [x] **F06-T04** · `back` Mapping Doctrine, migration `text`, `DoctrineTextRepository` — *Dépend de* : F06-T03, F00-T13 · *Fini quand* : suite abstraite verte sur PostgreSQL.

## Cas d'usage (US-06.1 à US-06.3)

- [x] **F06-T05** · `back` Commandes `CreateText` et `UpdateText` (nettoyage via le port avant construction du `RichText`) + tests fonctionnels — *Dépend de* : F06-T01, F06-T03 · *Fini quand* : corps vide après nettoyage → exception de validation.
- [ ] **F06-T06** · `back` Commande `DeleteText` : refus si attribué à une édition programmée (RG-TX-3), sinon retrait des attributions en brouillon + tests — *Dépend de* : F06-T05, F05-T10 · *Fini quand* : les deux cas testés.

## API d'administration (US-06.4)

- [ ] **F06-T07** · `back` `AdminTextViewFinder` : liste paginée, recherche titre/source (insensible aux accents), nombre d'attributions + tests d'intégration — *Dépend de* : F06-T04 · *Fini quand* : « therese » trouve « Thérèse » (test).
- [ ] **F06-T08** · `back` `GET /api/admin/texts` (collection, item) + tests d'acceptation — *Dépend de* : F06-T07, F04-T02 · *Fini quand* : pagination Hydra et JSON Schema validés.
- [ ] **F06-T09** · `back` `POST` / `PATCH` / `DELETE /api/admin/texts` : payloads, processors, 422 (corps vide), 409 (suppression refusée) + tests d'acceptation — *Dépend de* : F06-T06, F06-T08 · *Fini quand* : bornes visibles dans l'OpenAPI, codes testés.

## Écrans (US-06.1 à US-06.4)

- [ ] **F06-T10** · `front` `RichTextEditor` Tiptap 3 (Document, Paragraph, Text, Bold, Italic, Blockquote, HardBreak, History), barre d'outils accessible + tests — *Dépend de* : F01-T09 · *Fini quand* : utilisable au clavier, boutons annoncés avec leur état.
- [ ] **F06-T11** · `front` `RichTextView` : rendu du HTML nettoyé côté serveur, typographie de lecture (≥ 18 px, retours à la ligne des psaumes) + test — *Dépend de* : F01-T05 · *Fini quand* : rendu identique à l'aperçu de l'éditeur.
- [ ] **F06-T12** · `front` Écran « Textes » : liste, recherche, nombre d'attributions, état vide — *Dépend de* : F06-T08, F04-T04 · *Fini quand* : test Vitest/MSW.
- [ ] **F06-T13** · `front` Formulaire de texte (titre, source, corps, pistes) avec aperçu, erreurs 422 + tests — *Dépend de* : F06-T09, F06-T10, F06-T11, F06-T12 · *Fini quand* : création et modification testées.
- [ ] **F06-T14** · `front` Suppression avec confirmation, message explicite si refusée (409) + test — *Dépend de* : F06-T13 · *Fini quand* : les deux issues testées.

## Données et parcours

- [ ] **F06-T15** · `back` Fixtures : 48 textes du domaine public (Thérèse d'Avila, Jean de la Croix, psaumes…) — *Dépend de* : F06-T05, F05-T17 · *Fini quand* : `make fixtures` crée la bibliothèque.
- [ ] **F06-T16** · `test` E2E organisateur : créer un texte mis en forme, le modifier, le supprimer — *Dépend de* : F06-T14 · *Fini quand* : mise en forme conservée après rechargement, axe-core vert.
