# F16 — Pilotage des inscriptions (tableau de bord, gestion, export)

| Lot | Priorité | Estimation |
|---|---|---|
| 2 — Participation | MVP | <!-- est -->15 tâches · 7,5 h<!-- /est --> |

## Objectif et valeur

Donner aux organisateurs une vue d'ensemble de l'édition courante (**tableau de bord** : statut, créneaux vides, inscriptions, priants distincts, pointages) et les outils pour **gérer les inscriptions** : liste par créneau avec e-mails, inscription d'une personne reçue par téléphone (priant existant retrouvé par recherche, ou nouveau priant avec e-mail facultatif), renommage d'un priant, suppression et déplacement d'une inscription, et **export CSV** des inscrits (nom, e-mail, créneaux en heure de Paris).

Valeur : une partie du public (personnes âgées) passe par téléphone ; sans cet outil, ces priants seraient exclus. Le tableau de bord indique quand relancer. L'export CSV (arbitrage Q11, T55) sert aussi au **plan de secours** : en cas de panne du site pendant l'édition, les organisateurs disposent des adresses pour écrire aux priants hors application.

> **Note PO** : le §15 ne plaçait ni le tableau de bord ni la gestion des inscriptions dans un lot ; ils sont au lot 2 pour être prêts **dès l'ouverture du 13/11**.

## Personas concernés

- **Anne, organisatrice** : inscrit par téléphone la grand-mère de Thomas (sans e-mail), déplace une inscription, suit le remplissage, exporte la liste la veille de l'édition.
- **Marie, 74 ans** (indirectement) : peut être inscrite sans utiliser l'application.

## User stories

### US-16.1 — Suivre l'édition
En tant qu'**organisatrice**, je veux un tableau de bord de l'édition courante afin de savoir où en est la chaîne.

- **Étant donné** l'édition ouverte, **quand** j'ouvre le tableau de bord, **alors** je vois le statut, les créneaux vides (cliquables), le nombre d'inscriptions, le nombre de priants distincts et le nombre de pointages.

### US-16.2 — Consulter les inscriptions
En tant qu'**organisatrice**, je veux la liste des inscriptions par créneau, avec nom et e-mail, afin de contacter quelqu'un si besoin.

- **Étant donné** un créneau, **quand** je le filtre, **alors** je vois ses inscriptions, leur origine (priant ou organisateur) et leur pointage ; les e-mails ne sont servis que par `/api/admin`.

### US-16.3 — Inscrire quelqu'un reçu par téléphone
En tant qu'**organisatrice**, je veux inscrire une personne sur un ou plusieurs créneaux afin d'inclure ceux qui n'utilisent pas internet.

- **Étant donné** une personne déjà connue, **quand** je la recherche par nom ou par e-mail, **alors** je l'inscris sous son priant existant (un priant, un nom).
- **Étant donné** une nouvelle personne sans e-mail, **quand** je saisis son nom, **alors** un priant sans e-mail est créé, l'inscription apparaît dans la grille, aucune notification n'est envoyée (RG-NO-3) et aucun consentement n'est demandé (T46).
- **Étant donné** une nouvelle personne avec e-mail, **quand** je l'enregistre, **alors** elle reçoit la confirmation d'inscription (lien et `.ics`) ; mon appareil ne reçoit **pas** de pass appareil.

### US-16.4 — Renommer, supprimer ou déplacer
En tant qu'**organisatrice**, je veux corriger un nom, supprimer ou déplacer une inscription afin de tenir compte d'un appel téléphonique.

- **Étant donné** un priant, **quand** je corrige son nom, **alors** le nouveau nom s'applique partout (même règle que dans Mon espace).
- **Étant donné** une inscription avec e-mail, **quand** je la déplace de 3h00 à 4h00, **alors** la personne reçoit un e-mail « Mise à jour de votre inscription » avec un `.ics` mis à jour (même UID, `SEQUENCE` incrémentée).
- **Étant donné** un créneau cible où ce priant est déjà inscrit, **quand** je déplace, **alors** le déplacement est refusé avec un message clair (409).

### US-16.5 — Exporter les inscrits
En tant qu'**organisatrice**, je veux télécharger la liste des inscrits de l'édition afin de disposer d'une copie hors de l'application (plan de secours).

- **Étant donné** l'édition courante, **quand** je clique sur « Exporter (CSV) », **alors** je télécharge un fichier lisible dans un tableur (nom, e-mail, créneaux en heure de Paris), réservé aux organisateurs.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : §5.9 modules 1 (tableau de bord) et 4 (inscriptions, export), RG-NO-3, RG-IN-2 (un priant, un nom ; renommage par un organisateur), RG-IN-3, §5.10 (exception assumée sur le consentement).
- **Décisions techniques** : T17 (e-mails seulement sous `/api/admin`), T46, T55 (export CSV) ; §4.4 (`RegisterInscriptionsForSomeone`, `MoveInscription`), §5.2 (`/api/admin/priants/{id}`, `/api/admin/editions/{id}/inscriptions.csv`), §7.2.

## Dépendances

- F09 (priants, inscriptions), F11 (confirmation, `.ics`), F14 (`RenamePriant`, `CancelInscription`), F04 (back-office).

## Hors périmètre

- Modification de l'e-mail d'un priant par un organisateur.
- E-mail à la personne lors d'une suppression par un organisateur.
- Export multi-éditions ou statistiques avancées.

## Estimation

<!-- est -->**15 tâches × 0,5 h = 7,5 h** (10 `back`, 4 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
