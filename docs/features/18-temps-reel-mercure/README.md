# F18 — Temps réel (Mercure)

| Lot | Priorité | Estimation |
|---|---|---|
| 3 — Les 24 heures | Cible 1re édition | <!-- est -->6 tâches · 3 h<!-- /est --> |

## Objectif et valeur

Mettre à jour instantanément la grille, le bloc « En ce moment », le fil du relais et les intentions chez tous les visiteurs, grâce à des **signaux d'invalidation sans données** publiés sur un hub Mercure dédié. La visibilité reste appliquée par l'API au rechargement : aucune donnée personnelle ne transite par le hub.

Valeur : confort et sentiment de « chaîne vivante » (une inscription apparaît chez tout le monde en quelques secondes). **Ce n'est pas indispensable** : sans Mercure, le rafraîchissement toutes les 60 secondes (F08) suffit à une première édition — d'où la priorité « Cible 1re édition ». Une panne du hub ne bloque jamais l'usage (mode dégradé).

## Personas concernés

- **Claire, visiteuse** : voit un créneau passer de « À pourvoir » à « occupé » sous ses yeux.
- **Mainteneur d'astreinte** : un hub en panne ne dégrade que la fraîcheur, pas le service.

## User stories

### US-18.1 — Voir les changements en direct
En tant que **visiteuse**, je veux que la grille se mette à jour seule quand quelqu'un s'inscrit afin de suivre la chaîne en direct.

- **Étant donné** deux navigateurs sur l'accueil, **quand** une inscription est faite dans l'un, **alors** la grille et le bandeau de l'autre sont à jour en moins de 3 secondes.
- **Étant donné** un message publié sur le hub, **quand** on l'inspecte, **alors** il ne contient que des identifiants et un type (`slots`, `relay`, `intentions`, `edition`), jamais de nom ni d'e-mail.

### US-18.2 — Continuer à fonctionner sans le hub
En tant que **mainteneur**, je veux qu'une panne du hub fasse simplement revenir au rafraîchissement périodique afin de ne jamais bloquer l'usage.

- **Étant donné** le hub arrêté, **quand** un visiteur ouvre l'accueil, **alors** la page fonctionne et se rafraîchit toutes les 60 secondes et au retour sur l'onglet ; **quand** le hub revient, **alors** le direct reprend.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : §5.8 (faire vivre la chaîne) ; RG-PL-4 (aucun nom par le hub).
- **Décisions techniques** : T25, T26 ; §9, §12.1 (service `mercure`), §6.6 (CSP `connect-src 'self'`).

## Dépendances

- F17 (port `ChangeSignal` et listeners), F08 (polling de repli), F03 (stack, Traefik).
- F19, F20, F21 publient leurs signaux via le même port.

## Hors périmètre

- Topics privés ou données dans les messages (exclus par T26).
- Hub en grappe (la version libre ne le permet pas).

## Estimation

<!-- est -->**6 tâches × 0,5 h = 3 h** (1 `back`, 2 `front`, 2 `infra`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
