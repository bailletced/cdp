# F05 — Éditions et calendrier de Paris

| Lot | Priorité | Estimation |
|---|---|---|
| 1 — Programme | MVP | <!-- est -->22 tâches · 11 h<!-- /est --> |

## Objectif et valeur

Permettre aux organisateurs de créer, modifier et programmer une **édition** de 24 heures, découpée automatiquement en **48 créneaux réels de 30 minutes**, avec un statut calculé (brouillon, programmée, inscriptions ouvertes, en cours, terminée).

Le cœur de valeur est le **calcul du temps juste** : un service unique `ParisCalendar` produit les créneaux en UTC et leurs libellés en heure de Paris, y compris lors des changements d'heure (25/10/2026, 28/03/2027 = Pâques). Une erreur ici se propagerait au planning, aux e-mails, aux `.ics` et au rappel de la veille.

## Personas concernés

- **Anne, organisatrice** : prépare l'édition plusieurs semaines à l'avance, en heure de Paris.
- **Marie, priante** (indirectement) : voit des horaires justes, même la nuit du changement d'heure.

## User stories

### US-05.1 — Créer une édition
En tant qu'**organisatrice**, je veux créer une édition en brouillon (titre, présentation, date et heure de début en heure de Paris, date d'ouverture des inscriptions) afin de préparer l'événement.

- **Étant donné** un début le samedi 20h00 (heure de Paris), **quand** j'enregistre, **alors** l'édition est « Brouillon » et compte 48 créneaux de 20h00–20h30 à 19h30–20h00.
- **Étant donné** un début à 20h15, **quand** j'enregistre, **alors** une erreur m'indique que le début doit tomber sur une heure pleine ou une demi-heure (RG-ED-2).
- **Étant donné** un début saisi à 2h30 la nuit du passage à l'heure d'été (heure inexistante), **quand** j'enregistre, **alors** le début retenu est 3h30 et l'écran me l'indique ; une heure ambiguë (nuit du passage à l'heure d'hiver) retient la première occurrence (arbitrage Q13).
- **Étant donné** une ouverture postérieure au début, **quand** j'enregistre, **alors** l'enregistrement est refusé avec un message explicite.

### US-05.2 — Des horaires justes au changement d'heure
En tant qu'**organisatrice**, je veux que les créneaux durent réellement 30 minutes et que les libellés ambigus soient précisés afin que personne ne se trompe d'heure.

- **Étant donné** une édition couvrant la nuit du 25/10/2026, **quand** j'affiche les créneaux, **alors** « 2h00 (heure d'été) » et « 2h00 (heure d'hiver) » apparaissent tous deux.
- **Étant donné** une édition couvrant la nuit du 28/03/2027, **quand** j'affiche les créneaux, **alors** le créneau de 1h30 se termine à 3h00.

### US-05.3 — Modifier une édition
En tant qu'**organisatrice**, je veux modifier une édition tant que c'est utile afin de corriger une erreur.

- **Étant donné** une édition sans inscription, **quand** je change l'heure de début, **alors** les 48 créneaux sont régénérés.
- **Étant donné** deux organisatrices modifiant la même édition, **quand** la seconde enregistre, **alors** elle reçoit un conflit (409) l'invitant à recharger.

### US-05.4 — Programmer ou repasser en brouillon
En tant qu'**organisatrice**, je veux programmer une édition prête afin qu'elle s'ouvre seule à la date prévue.

- **Étant donné** une édition programmée dont l'ouverture est dans 3 jours, **quand** un visiteur consulte l'accueil, **alors** il ne la voit pas ; **quand** la date arrive, **alors** elle devient « Inscriptions ouvertes » sans action ni tâche de fond.
- **Étant donné** une édition programmée, **quand** je la repasse en brouillon, **alors** elle redevient invisible du public.

### US-05.5 — Consulter les éditions
En tant qu'**organisatrice**, je veux la liste des éditions avec leur statut et le détail des 48 créneaux groupés par moment de la journée afin de vérifier la préparation.

- **Étant donné** plusieurs éditions, **quand** j'ouvre « Éditions », **alors** je vois titre, dates en heure de Paris et statut en toutes lettres, y compris pour les éditions terminées.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : §5.1 (attributs, cycle de vie), RG-ED-1 (statut ; la sélection publique est en F08), RG-ED-2, RG-ED-3 (régénération ; le blocage après la première inscription est branché en F09), RG-ED-5.
- **Décisions techniques** : T9, T10, T11, T12, T45 ; §4.3 (`Edition`, `Slot`, verrou optimiste), §4.5, §4.6 (tests obligatoires), §4.7.

## Dépendances

- F00 (noyau), F04 (back-office, `/api/admin`).
- Prépare F06/F07 (textes), F08 (planning), F24 (échéance de la veille).

## Hors périmètre

- Attribution des textes et avertissement RG-ED-4 (→ F07).
- Duplication d'une édition (RG-ED-6, → F29).
- Durée de créneau paramétrable (fixée à 30 minutes, T45).

## Estimation

<!-- est -->**22 tâches × 0,5 h = 11 h** (17 `back`, 4 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
