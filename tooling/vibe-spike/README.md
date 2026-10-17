# Spike : Probity ↔ Mistral Vibe

Objectif : lever les inconnues avant d'écrire le moindre adaptateur.

| Inconnue | Comment la lever |
|---|---|
| Noms exacts de `tool_name` (`write_file`, `edit`, `bash`…) | `captures/*-pre_tool-*.json` |
| Forme de `tool_input` pour chaque outil | idem |
| Ce que désigne `transcript_path` (fichier, dossier) et son format | `captures/transcript-path.jsonl`, `captures/transcripts/` |
| Peut-on reconstituer l'historique depuis `post_tool` plutôt que depuis le journal de session ? | `captures/*-post_tool-*.json` (`tool_output_text`, `tool_status`) |
| Le hook se déclenche-t-il pour les outils de lecture (passthrough) ? | présence de `read_file`, `grep` dans les captures |

Le hook ne bloque et ne modifie rien : il écrit dans `captures/` (ignoré par git) et sort avec 0.

## Lancer la capture

```bash
cd tooling/vibe-spike
vibe
```

1. Vibe demande de **faire confiance au dossier** : accepte. Les hooks de projet ne se chargent que dans un dossier de confiance.
2. Donne ce prompt, qui exerce les trois outils d'écriture et un outil de lecture :

   > Crée `hello.php` qui affiche « Hello ». Lis-le, puis modifie-le pour afficher « Bonjour » à la place. Enfin vérifie sa syntaxe avec `php -l hello.php`.

3. Accepte les demandes de permission, puis quitte Vibe.
4. Vérifie que des fichiers sont apparus : `ls captures`.

Si rien n'apparaît : `captures/errors.log` et la sortie de `vibe` (onglet diagnostics) disent pourquoi. Les hooks étaient expérimentaux en 2.15.0 ; cette machine a Vibe 2.26.1.

## À me transmettre

Rien à copier-coller : dis-moi simplement « capture faite », je lis `captures/`.

Les captures contiennent le code écrit pendant la session et une copie éventuelle de la transcription. Elles restent locales (`/tooling/vibe-spike/captures/` est dans `.gitignore`).

## Suite prévue

1. Figer les captures comme fixtures de test du convertisseur.
2. Écrire le shim (renommage des outils, historique, réponse `{"decision":"deny"}`) avec des tests.
3. Brancher le validateur Mistral (`Config.ai`) et passer les scénarios TDD : test neuf, bouchon, sur-implémentation, écriture sans test, erreur du hook (avec `strict = true`).
4. Décider : on garde le shim, ou on propose un fournisseur `mistral-vibe` à Probity.
