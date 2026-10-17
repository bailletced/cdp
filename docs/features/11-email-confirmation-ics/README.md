# F11 — E-mail de confirmation et fichier agenda (.ics)

| Lot | Priorité | Estimation |
|---|---|---|
| 2 — Participation | MVP | <!-- est -->8 tâches · 4 h<!-- /est --> |

## Objectif et valeur

À chaque inscription, envoyer **un seul e-mail** récapitulant les créneaux pris, contenant le **texte complet** de chaque créneau, le **lien personnel** et un **fichier .ics** avec une alarme 10 minutes avant chaque créneau. Le fichier agenda est aussi téléchargeable depuis l'espace personnel.

Valeur : c'est l'e-mail qui « remet » le texte à méditer et la clé d'accès à l'espace ; l'alarme d'agenda est le seul rappel juste avant le créneau (aucun autre n'est prévu en V1), ce qui compte beaucoup pour les créneaux de nuit.

## Personas concernés

- **Marie, 74 ans** : retrouve son texte dans sa boîte, l'imprime ou le lit directement.
- **Thomas, 41 ans** : ajoute ses créneaux à son agenda en un geste, avec une alarme.
- **Claire, à Montréal** : son agenda convertit correctement l'heure (événements en heure absolue).

## User stories

### US-11.1 — Recevoir une confirmation complète
En tant que **priant**, je veux un e-mail de confirmation après mon inscription afin de garder une trace et d'avoir mes textes.

- **Étant donné** une inscription sur trois créneaux en une fois, **quand** elle est enregistrée, **alors** je reçois **un seul** e-mail listant les trois créneaux en heure de Paris (avec la mention « heure de Paris »), leurs textes complets et mon lien personnel.
- **Étant donné** un créneau sans texte, **quand** l'e-mail est rédigé, **alors** il indique « Le texte de ce créneau sera bientôt disponible ».
- **Étant donné** une inscription sans e-mail (saisie par un organisateur), **quand** elle est enregistrée, **alors** aucun e-mail ne part (RG-NO-3).

### US-11.2 — Ajouter mes créneaux à mon agenda
En tant que **Thomas**, je veux un fichier .ics joint et téléchargeable afin que mon agenda me prévienne.

- **Étant donné** le `.ics` joint, **quand** je l'importe dans mon agenda, **alors** chaque créneau devient un événement à la bonne heure absolue, avec une alarme 10 minutes avant.
- **Étant donné** une session ouverte, **quand** je clique sur le bouton unique « Ajouter mes créneaux à mon agenda » de Mon espace, **alors** je télécharge un seul fichier contenant tous mes créneaux de l'édition en cours ; il n'y a pas de bouton par créneau (arbitrage Q15).
- **Étant donné** une inscription modifiée ou déplacée, **quand** je réimporte le fichier, **alors** l'événement est mis à jour, sans doublon (même UID, `SEQUENCE` incrémentée).

### US-11.3 — Un e-mail lisible partout
En tant qu'**organisatrice**, je veux un e-mail sobre aux couleurs de la Communion, lisible sur téléphone et dans les principales messageries, afin d'inspirer confiance.

- **Étant donné** l'e-mail de confirmation, **quand** il est ouvert dans Gmail, Outlook et Apple Mail (iOS), **alors** la mise en page et le lien personnel fonctionnent ; une version texte existe.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : RG-IN-1 (e-mail de confirmation), RG-NO-1, RG-NO-2, RG-NO-3 ; §5.7 (ligne « Confirmation d'inscription »).
- **Décisions techniques** : T28 (eluceo/ical), T29 (envoi asynchrone), T30 (Mailer, gabarits Twig, suivi des clics désactivé) ; §7.2, §10.2.

## Dépendances

- F09 (inscriptions, événement `InscriptionsRegistered`), F02 (gabarit de base, `PersonalLinkIssuer`), F06 (textes), F10 (`Viewer` pour le téléchargement).

## Hors périmètre

- E-mail de mise à jour après un déplacement par un organisateur (→ F16, réutilise le port `CalendarFile`).
- Rappel de la veille (→ F24), rappel juste avant le créneau (hors périmètre V1).
- E-mail de désinscription (exclu par les specs : confirmation à l'écran).

## Estimation

<!-- est -->**8 tâches × 0,5 h = 4 h** (6 `back`, 2 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
