# F29 — Reconduction : duplication, historique, invitations et désabonnement

| Lot | Priorité | Estimation |
|---|---|---|
| 4 — Communication (réalisé après la 1re édition) | Après 1re édition | <!-- est -->13 tâches · 6,5 h<!-- /est --> |

## Objectif et valeur

Rendre l'application **réutilisable d'une année sur l'autre** : créer la nouvelle édition en dupliquant la précédente (présentation et attribution des textes reprises), offrir à chaque priant l'**historique** de ses créneaux passés, **inviter** les priants des éditions précédentes (consentants, non désabonnés, pas encore inscrits) avec un lien personnel qui les fait reconnaître, et leur permettre de **se désabonner** en un clic.

Valeur : forte à partir de la deuxième édition (c'est le moteur de la fidélisation), **nulle pour la première** : il n'existe encore ni édition précédente, ni historique, ni ancien priant à inviter. D'où le report après la première édition, en écart assumé avec l'ordre du §15 (lot 4).

## Personas concernés

- **Anne, organisatrice** : prépare l'édition de l'an prochain en dupliquant celle-ci, puis invite les anciens priants.
- **Thomas, 41 ans** : reçoit l'invitation, arrive reconnu sur le planning, formulaire pré-rempli.
- **Claire, 29 ans** : ne souhaite plus recevoir d'invitations et se désabonne en un clic.
- **Marie, 74 ans** : relit les textes qu'elle a médités l'an dernier.

## User stories

### US-29.1 — Dupliquer une édition
En tant qu'**organisatrice**, je veux créer une édition en dupliquant la précédente afin de ne pas refaire la présentation et l'attribution des 48 textes (RG-ED-6).

- **Étant donné** l'édition 2027 terminée, **quand** je la duplique avec de nouvelles dates, **alors** une édition « Brouillon » est créée avec la même présentation et les mêmes textes par rang, sans aucune inscription.

### US-29.2 — Consulter mon historique
En tant que **Marie**, je veux retrouver mes créneaux et leurs textes des éditions précédentes afin de les relire.

- **Étant donné** des inscriptions en 2027, **quand** j'ouvre « Historique » en 2028, **alors** je vois l'édition 2027, mes créneaux et les liens vers leurs textes.

### US-29.3 — Inviter les anciens priants
En tant qu'**organisatrice**, je veux inviter les priants des éditions précédentes afin qu'ils reviennent.

- **Étant donné** la nouvelle édition aux inscriptions ouvertes, **quand** je prépare l'invitation, **alors** l'aperçu indique le nombre de destinataires : priants des éditions précédentes ayant donné leur consentement, non désabonnés, non effacés et pas encore inscrits.
- **Étant donné** une édition seulement programmée (inscriptions pas encore ouvertes), **quand** je tente d'inviter, **alors** l'envoi est refusé (le lien mènerait à un planning invisible).
- **Étant donné** l'invitation reçue, **quand** Thomas clique sur le lien, **alors** il arrive sur le planning, reconnu (session ouverte), et le formulaire d'inscription est pré-rempli (RG-IN-7).

### US-29.4 — Me désabonner
En tant que **Claire**, je veux me désabonner des invitations en un clic, depuis l'e-mail ou depuis mon espace.

- **Étant donné** une invitation, **quand** ma messagerie utilise le désabonnement intégré (`List-Unsubscribe-Post`) ou que je clique sur le lien, **alors** je suis désabonnée sans me connecter et je ne reçois plus d'invitation.
- **Étant donné** mon espace, **quand** j'ouvre « Mes données », **alors** je vois mon statut et peux me désabonner.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : RG-ED-6, RG-ED-1 (éditions précédentes consultables dans l'historique), RG-IN-7, §5.5 (« Historique », « Mes données » : désabonnement), §5.7 (« Invitation à une nouvelle édition »), §5.10 (désabonnement).
- **Décisions techniques** : T16 (lien d'invitation = lien personnel) ; §4.4 (`DuplicateEdition`, `UnsubscribeInvitations`), §5.2 (`/unsubscribe/{token}`, `/api/me/invitations-unsubscribe`), §7.2 (RFC 8058).

## Dépendances

- F23 (moteur de campagnes), F07 (attributions), F14 et F25 (espace, « Mes données »), F12 (lecture des textes passés), F02 (lien personnel).

## Hors périmètre

- Réabonnement en libre-service (sur demande aux organisateurs).
- Invitation ciblée (par ancienneté, par créneau habituel).

## Estimation

<!-- est -->**13 tâches × 0,5 h = 6,5 h** (8 `back`, 4 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
