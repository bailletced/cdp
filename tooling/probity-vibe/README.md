# Probity pour Mistral Vibe

Fait appliquer à **Mistral Vibe** les règles Probity du projet (`probity.config.mjs`), notamment le TDD sur `api/src`, `api/tests`, `front/src` et `front/e2e`. Probity ne gère pas Vibe nativement ; ce dossier est une couche de traduction.

## Comment ça marche

```
Vibe ──pre_tool──▶ hook.mjs ──▶ Probity (--agent claude-code) ──▶ validateur Mistral
  ▲                  │  traduit write_file / search_replace / bash         │
  │                  │  vers Write / Edit / Bash                           │
  └── {"decision":"deny","reason":…} ◀───────────────────────────────────────┘
Vibe ──post_tool─▶ hook.mjs ──▶ historique de session (JSONL au format Claude Code)
```

- **Pourquoi un historique maison ?** Vibe 2.26 ne fournit pas de fichier de transcription exploitable : `transcript_path` désigne un dossier au format interne. Le hook `post_tool` reconstitue donc l'historique (appels, sorties, refus) que Probity montre au validateur.
- **Noms d'outils :** Vibe 2.26.1 expose `file_system.write_file`, `file_system.search_replace`, `file_system.bash`. Le préfixe est retiré et `edit` est accepté comme synonyme de `search_replace`, car les noms varient selon les versions.
- **Sûr par défaut :** tout échec (config introuvable, Probity qui plante, validateur injoignable, réponse illisible, clé absente) produit un **refus**, jamais un passage.

## Mise en place

1. Exporter `MISTRAL_API_KEY` dans l'environnement qui lance `vibe`. Ne jamais la committer : le dépôt est public.
2. Copier `hooks.toml.example` vers `.vibe/hooks.toml` (ou `~/.vibe/hooks.toml`), puis accepter la confiance du dossier au prochain lancement de `vibe`.
3. Facultatif : `PROBITY_MISTRAL_MODEL` (défaut `mistral-large-latest`), `PROBITY_VIBE_HISTORY_DIR`, `PROBITY_VIBE_CONFIG`.

`strict = true` est indispensable sur `probity-pre` : sans lui, une panne du hook laisse passer l'action.

## Tests

```bash
pnpm test:vibe
```

36 tests, sans réseau ni clé : traduction des outils (avec des payloads réels de Vibe 2.26.1, chemins anonymisés dans `test/fixtures/`), historique, réponses, validateur Mistral (faux `fetch`), hook contre le **vrai** Probity, et chaîne complète contre la vraie config du dépôt avec un faux serveur Mistral.

## Limites connues

- **Pas encore éprouvé en vrai.** Les tests couvrent l'adaptateur, pas une session Vibe réelle avec le modèle Mistral : voir « Vérification en réel » ci-dessous.
- **L'historique n'a ni les prompts utilisateur ni les actions antérieures à l'installation du hook** ; il vit dans `<tmp>/probity-vibe/<session>.jsonl`.
- **`bash` peut écrire des fichiers** (`cat > …`), ce que les règles d'écriture ne voient pas. C'est la même faiblesse qu'avec Claude Code.
- **L'agent peut modifier `.vibe/hooks.toml`** ou `probity.config.mjs`. À protéger par une règle si le risque compte.
- **Qualité du jugement :** le prompt de Probity est long et nuancé ; la fiabilité d'un modèle Mistral à le suivre est à mesurer.

## Vérification en réel

1. `export MISTRAL_API_KEY=…`, copier `hooks.toml.example` dans `tooling/vibe-spike/.vibe/hooks.toml` (remplace la capture), lancer `vibe` dans ce dossier.
2. Demander : « Crée le fichier `<racine du dépôt>/api/src/Spike/Probe.php` contenant une classe vide `Probe`. »
3. Attendu : l'écriture est **refusée**, avec un motif de Probity demandant d'écrire d'abord un test qui échoue.
4. Demander ensuite une écriture hors des dossiers gardés (`docs/notes.md`) : attendue **acceptée**, sans appel au validateur.
