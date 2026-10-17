# F23 — Campagnes d'e-mails : relance « créneaux vides », message libre et quota journalier

| Lot | Priorité | Estimation |
|---|---|---|
| 4 — Communication | MVP | <!-- est -->15 tâches · 7,5 h<!-- /est --> |

## Objectif et valeur

Donner aux organisateurs un moteur d'**envoi en masse fiable** (destinataires figés, une livraison par destinataire, idempotence, avancement visible, historique) **qui respecte le quota de l'offre gratuite de Brevo** (300 e-mails par jour, T47, T56), et deux usages :

- la **relance « créneaux vides »** à tous les priants de l'édition, avec la liste des créneaux encore vides et des liens directs pour s'y inscrire ;
- le **message libre** (objet et corps rédigés par l'organisateur), utile aussi pour la communication de crise.

**Quota** : deux transports Messenger. `async_transactional` (confirmation, lien personnel, mise à jour, rappel de la veille) est consommé en priorité ; `async_bulk` (campagnes) est soumis à une garde de quota journalier (limite configurable à 300, avec une réserve pour le transactionnel) qui **reporte au lendemain 8h (heure de Paris)** les messages qui dépasseraient le quota du jour. Avec 50 à 150 priants (Q24), une campagne tient en général en une journée ; le report protège les e-mails transactionnels.

Valeur : la relance est le levier le plus puissant pour remplir les derniers créneaux de nuit (objectif n° 2) ; le moteur est réutilisé par le rappel de la veille (F24) et l'invitation (F29). Chaque e-mail porte le lien personnel du destinataire.

## Personas concernés

- **Anne, organisatrice** : le 19/11, il reste 6 créneaux vides ; elle relance tous les inscrits.
- **Thomas, 41 ans** : reçoit la relance et prend un créneau en un clic depuis l'e-mail.
- **Mainteneur** : s'assure qu'un message rejoué n'est jamais envoyé deux fois et que le quota n'est jamais dépassé.

## User stories

### US-23.1 — Relancer pour les créneaux vides
En tant qu'**organisatrice**, je veux envoyer une relance listant les créneaux vides afin de compléter la chaîne.

- **Étant donné** 6 créneaux vides à venir, **quand** je prépare une relance, **alors** l'aperçu montre mon introduction (modifiable) suivie des 6 horaires en heure de Paris, chacun avec un lien direct vers l'inscription, et le nombre de destinataires.
- **Étant donné** l'aperçu, **quand** je confirme explicitement l'envoi, **alors** chaque priant de l'édition ayant un e-mail (non effacé) reçoit **un** e-mail avec son lien personnel.

### US-23.2 — Envoyer un message libre
En tant qu'**organisatrice**, je veux écrire un message libre (objet, corps) à tous les priants de l'édition afin de les informer (changement, remerciement, incident).

- **Étant donné** un objet et un corps, **quand** je confirme après l'aperçu, **alors** le message part à tous les priants de l'édition ayant un e-mail.

### US-23.3 — Respecter le quota journalier
En tant que **mainteneur**, je veux que les envois groupés ne dépassent jamais le quota du jour afin que les confirmations et les liens personnels partent toujours.

- **Étant donné** un quota restant de 120 envois groupés et 200 destinataires, **quand** je prépare l'envoi, **alors** l'aperçu annonce « 120 aujourd'hui, 80 demain à partir de 8h » ; après confirmation, 80 messages sont reportés au lendemain 8h (heure de Paris).
- **Étant donné** un rappel de la veille prévu dans la fenêtre d'envoi, **quand** je prépare une campagne, **alors** l'aperçu m'en avertit.
- **Étant donné** des envois groupés en cours, **quand** un priant s'inscrit, **alors** sa confirmation part aussitôt (transport prioritaire, réserve du quota).

### US-23.4 — Suivre les envois
En tant qu'**organisatrice**, je veux l'historique des envois et leur avancement afin de savoir ce qui est parti.

- **Étant donné** un envoi en cours, **quand** j'ouvre « Envois », **alors** je vois pour chacun : type, objet, auteur, date, destinataires, envoyés / en attente / reportés / en échec.
- **Étant donné** un message rejoué après une panne du worker, **quand** il est traité, **alors** le destinataire ne reçoit pas l'e-mail deux fois.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : §5.7 (« Relance créneaux vides », « Message libre »), §5.3-5, §5.9 module 6 (aperçu, nombre de destinataires, confirmation, historique), RG-NO-1, RG-NO-3.
- **Décisions techniques** : T29, T30, T47 (Brevo, 300/jour), T56 (transports `async_transactional` / `async_bulk`, quota avec report) ; §4.3 (`Campaign`, `Delivery`), §7.1, §7.3.

## Dépendances

- F11 (`Notifier`), F02 (`PersonalLinkIssuer`), F13 (créneaux vides), F09 (priants), F04 (back-office), F06 (éditeur pour le corps), F05 (jour de Paris), F17 (horloge pilotable pour l'e2e du report).

## Hors périmètre

- Invitation à une nouvelle édition (→ F29), rappel de la veille (→ F24).
- Envoi programmé à une date choisie, ciblage fin (par créneau), SMS.
- Passage à une offre Brevo payante (à décider si le volume dépasse les prévisions).

## Estimation

<!-- est -->**15 tâches × 0,5 h = 7,5 h** (11 `back`, 3 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
