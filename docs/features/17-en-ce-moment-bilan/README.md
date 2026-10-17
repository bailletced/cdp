# F17 — « En ce moment » et bilan

| Lot | Priorité | Estimation |
|---|---|---|
| 3 — Les 24 heures | MVP | <!-- est -->10 tâches · 5 h<!-- /est --> |

## Objectif et valeur

Faire **vivre la chaîne** pendant les 24 heures : sur l'accueil, un bloc « En ce moment » montre le créneau en cours, qui prie (nombre pour un visiteur, noms pour un inscrit), la progression de la chaîne et le créneau suivant ; si personne ne prie en ce moment, il invite à rejoindre la chaîne **tout de suite**. Après la fin, le bloc devient un **bilan**.

La feature pose aussi deux briques transverses :

- le port `ChangeSignal` (avec un adaptateur nul par défaut) et ses listeners, que Mercure (F18) branchera ;
- une **horloge pilotable réservée à l'environnement e2e**, indispensable pour tester le déroulé des 24 heures.

## Personas concernés

- **Claire, visiteuse** : voit que la chaîne est vivante et la rejoint si un créneau est vide.
- **Marie, 74 ans** : voit qui prie juste avant elle.
- **Anne, organisatrice** : partage le bilan après l'édition.

## User stories

### US-17.1 — Voir où en est la chaîne
En tant que **visiteuse**, je veux voir le créneau en cours, le nombre d'inscrits, la progression et le créneau suivant afin de sentir la chaîne vivante.

- **Étant donné** l'édition en cours à 3h10, **quand** j'ouvre l'accueil, **alors** je vois « 3h00–3h30 », le nombre d'inscrits, « 7 créneaux écoulés sur 48 », le temps restant et « Ensuite : 3h30–4h00 ».
- **Étant donné** un inscrit, **quand** il ouvre l'accueil, **alors** il voit les noms des priants du créneau en cours.
- **Étant donné** 3h29:59 puis 3h30:00 (heure serveur), **quand** le temps passe, **alors** le bloc bascule seul sur le créneau suivant, sans recharger la page.

### US-17.2 — Rejoindre la chaîne quand elle est vide
En tant que **Claire**, je veux être invitée à prier maintenant si personne n'est inscrit afin que la chaîne ne s'interrompe pas.

- **Étant donné** le créneau en cours vide, **quand** j'ouvre l'accueil, **alors** je lis « Personne n'est inscrit en ce moment — rejoignez la chaîne » avec un bouton qui m'inscrit sur le créneau en cours.

### US-17.3 — Lire le bilan
En tant qu'**organisatrice**, je veux un bilan après l'édition afin de remercier et d'encourager.

- **Étant donné** l'édition terminée, **quand** on ouvre l'accueil, **alors** le bloc affiche le nombre de créneaux couverts sur 48, le nombre de priants distincts et un message de remerciement.

### US-17.4 — Tester le déroulé
En tant que **développeur**, je veux piloter l'horloge en e2e afin de rejouer les 24 heures en quelques minutes.

- **Étant donné** l'environnement e2e, **quand** je règle l'horloge sur « début + 3 h », **alors** l'API et `X-Server-Time` reflètent cette heure ; **en production**, ce réglage n'existe pas.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : §5.8 « Bloc En ce moment » (dont le bilan), §5.1 « En cours » et « Terminée », RG-PL-4 (noms pour les seuls inscrits).
- **Décisions techniques** : T9, T11, T26 (port `ChangeSignal`) ; §5.1 (`X-Server-Time`), §8.4 (horloge corrigée), §14.1 (horloge pilotable uniquement en e2e).

## Dépendances

- F08 (accueil, horloge corrigée), F09 et F10 (inscriptions, politique), F14 et F16 (événements d'inscription).

## Hors périmètre

- Mise à jour instantanée par Mercure (→ F18) ; ici, rafraîchissement toutes les 60 s (F08) et bascule horaire côté client.
- Pointage (→ F19).

## Estimation

<!-- est -->**10 tâches × 0,5 h = 5 h** (5 `back`, 4 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
