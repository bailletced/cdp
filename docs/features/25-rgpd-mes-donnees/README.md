# F25 — Données personnelles : « Mes données » et confidentialité

| Lot | Priorité | Estimation |
|---|---|---|
| 5 — Finitions | MVP | <!-- est -->10 tâches · 5 h<!-- /est --> |

## Objectif et valeur

Permettre à chaque priant de **supprimer toutes ses données en libre-service** (inscriptions à venir annulées, inscriptions passées anonymisées en « Un priant » pour garder les statistiques, mots de relais et intentions supprimés, accès révoqués), et publier la page **« Confidentialité et mentions légales »** avec le libellé définitif du consentement.

Valeur : conformité minimale au RGPD (base légale, information, droit à l'effacement) et confiance des priants. Les écarts assumés (conservation sans limite, inscriptions saisies par un organisateur sans consentement, données de santé possibles dans les intentions) sont documentés.

> **Jalon** : la page « Confidentialité » et le libellé du consentement (T07, T08) doivent être en ligne **avant l'ouverture des inscriptions** de la première édition ; le reste peut suivre avant l'événement.

## Personas concernés

- **Claire, 29 ans** : veut savoir ce qui est conservé et pouvoir tout effacer.
- **Responsable du traitement (la Communion)** : doit pouvoir justifier ses choix.
- **Anne, organisatrice** : n'a pas à traiter les demandes d'effacement à la main.

## User stories

### US-25.1 — Supprimer mes données
En tant que **priant vérifié**, je veux supprimer toutes mes données en un geste, après confirmation, afin d'exercer mon droit à l'effacement.

- **Étant donné** des inscriptions à venir et passées, **quand** je confirme deux fois la suppression, **alors** mes inscriptions à venir sont supprimées (les créneaux concernés peuvent redevenir vides), mes inscriptions passées apparaissent sous le nom « Un priant », mes mots de relais et intentions sont supprimés et mon e-mail est effacé.
- **Étant donné** la suppression effectuée, **quand** j'utilise un ancien lien personnel ou un autre appareil connecté, **alors** l'accès est refusé (sessions, remember-me et passes révoqués).
- **Étant donné** une organisatrice qui est aussi priante, **quand** elle supprime ses données de priante, **alors** son rôle d'organisatrice est conservé, ses accès sont révoqués et elle doit redemander un lien (arbitrage Q17).
- **Étant donné** la suppression, **quand** l'opération échoue en cours de route, **alors** rien n'est modifié (une seule transaction côté Chain).

### US-25.2 — Être informé
En tant que **visiteuse**, je veux une page « Confidentialité et mentions légales » accessible depuis toutes les pages afin de savoir qui traite mes données, pourquoi et combien de temps.

- **Étant donné** n'importe quelle page, **quand** je clique sur « Confidentialité » dans le pied de page, **alors** je lis le responsable du traitement, les finalités, la base légale (consentement), la durée de conservation (sans limite, écart assumé), mes droits, les sous-traitants et le contact.
- **Étant donné** le formulaire d'inscription, **quand** je lis la case de consentement, **alors** son libellé est celui validé par la Communion et renvoie vers cette page.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : RG-EP-3, §5.5 « Mes données » (suppression), §5.10 (données, base légale, droits, page « Confidentialité »).
- **Décisions techniques** : T19 (anonymisation sur place via `PriantErased` ; modèle v0.3 : `priantId` obligatoire, c'est le `Priant` qui est anonymisé), T20, T46 ; §6.3 (révocation), §11.

## Dépendances

- F09, F10, F14 (inscriptions, passes, espace), F02 (sessions, `credentialsVersion`), F01 (route `/confidentialite`).
- F20 et F21 si livrées (suppression des mots de relais et intentions : tâche T03).
- **Contenu juridique** : brouillon rédigé par Claude, validé par la Communion avant l'ouverture du 13/11 (arbitrage Q3).

## Hors périmètre

- Désabonnement des invitations (→ F29).
- Purge automatique après une durée de conservation (écart assumé T20 ; ajoutable sans refonte).
- Export de ses données (portabilité) : sur demande aux organisateurs.

## Estimation

<!-- est -->**10 tâches × 0,5 h = 5 h** (5 `back`, 2 `front`, 1 `test`, 2 `doc`). Détail dans [taches.md](taches.md).<!-- /est -->
