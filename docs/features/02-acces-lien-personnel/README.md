# F02 — Accès par lien personnel (squelette de bout en bout)

| Lot | Priorité | Estimation |
|---|---|---|
| 0 — Socle | MVP | <!-- est -->20 tâches · 10 h<!-- /est --> |

## Objectif et valeur

Permettre à quiconque possède un compte (priant ou organisateur) d'ouvrir une **session durable sans mot de passe** grâce au lien personnel reçu par e-mail. C'est la clé de voûte de toutes les actions « au nom du priant » (RG-IN-6) et de l'accès au back-office.

Cette feature porte aussi le **squelette de bout en bout** du lot 0 : e-mail asynchrone (Mailpit) → lien → session → `/mon-espace`. Il traverse les zones absentes d'apip-ddd (authentification, e-mails, worker) et les dérisque dès le départ.

> **Choix PO** : le §15 prévoit « inscription minimale → e-mail → lien ». Pour éviter du code jetable (une inscription suppose éditions et créneaux, livrés au lot 1), le squelette passe par « Recevoir mon lien » sur un compte amorcé par `app:organizer:create`. Le même chemin technique est couvert (voir questions ouvertes de l'index).

## Personas concernés

- **Thomas, 41 ans, père de famille** : retrouve son espace depuis n'importe quel e-mail reçu.
- **Marie, 74 ans** : ne retient aucun mot de passe ; un lien suffit et la session reste ouverte.
- **Anne, organisatrice** : se connecte au back-office par le même mécanisme.

## User stories

### US-02.1 — Recevoir mon lien personnel
En tant que **priant**, je veux saisir mon e-mail pour recevoir mon lien personnel afin de retrouver mon espace sur un nouvel appareil.

- **Étant donné** une adresse connue, **quand** je valide le formulaire, **alors** je vois « Si cette adresse est connue, un lien vous a été envoyé » et je reçois un e-mail contenant mon lien.
- **Étant donné** une adresse inconnue, **quand** je valide, **alors** je vois exactement le même message, avec le même temps de réponse, et aucun e-mail ne part (pas de fuite d'information).

### US-02.2 — Ouvrir ma session par le lien
En tant que **priant**, je veux cliquer sur mon lien pour arriver connecté dans mon espace afin de gérer mes inscriptions.

- **Étant donné** un lien valide, **quand** je l'ouvre, **alors** la page `/connexion` confirme automatiquement (POST) puis m'amène sur `/mon-espace`.
- **Étant donné** une messagerie qui précharge les liens (GET seul), **quand** elle visite le lien, **alors** aucune session n'est ouverte et le lien reste utilisable.
- **Étant donné** un lien expiré, altéré ou révoqué, **quand** je l'ouvre, **alors** un message clair me propose d'en recevoir un nouveau.
- **Étant donné** un lien demandé hors édition, ou le lien d'un organisateur, **quand** 8 jours ont passé, **alors** il est expiré ; pendant une édition, le lien d'un priant reste valable jusqu'à la fin de l'édition + 7 jours (arbitrage Q8).

### US-02.3 — Rester connecté durablement
En tant que **Marie**, je veux rester reconnue sur mon téléphone afin de ne pas redemander un lien à chaque visite.

- **Étant donné** une session ouverte, **quand** je reviens après la fermeture du navigateur, **alors** je suis toujours connectée (remember-me d'un an, révocable).

### US-02.4 — Me déconnecter
En tant que **priant sur un appareil partagé**, je veux me déconnecter afin que personne n'agisse en mon nom.

- **Étant donné** une session ouverte, **quand** je clique sur « Me déconnecter », **alors** la session et le jeton remember-me sont supprimés.

### US-02.5 — Amorcer le premier organisateur
En tant que **mainteneur**, je veux créer le premier organisateur en ligne de commande afin d'ouvrir l'accès au back-office.

- **Étant donné** une base vide, **quand** je lance `app:organizer:create anne@exemple.fr`, **alors** le compte et le rôle organisateur existent ; relancer la commande ne crée pas de doublon.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : §5.5 « Accès » (lien personnel, « Recevoir mon lien », message identique, session durable), RG-EP-2 (lien impossible à deviner), RG-IN-6 (partiel : les actions exigent une session), §5.9 « Accès » (connexion des organisateurs par lien), §6 Sécurité.
- **Décisions techniques** : T13, T14, T29, T30 ; §6.1 à §6.3, §6.6 (CSRF, origine), §7.2.

## Dépendances

- F00 (socle), F01 (composants de formulaire, layout).
- Prépare F03 (le squelette est rejoué en production), F04, F09 (création du compte à l'inscription), F10 (pass appareil dans le `Viewer`).

## Hors périmètre

- Pass appareil et vue inscrit en lecture (→ F10).
- Limitation du nombre de demandes et ALTCHA (→ F15) ; seul le champ piège est posé ici.
- Lien d'invitation avec cible « planning » (→ F29).
- Révocation par un organisateur via une interface (→ commande d'exploitation en F27).

## Estimation

<!-- est -->**20 tâches × 0,5 h = 10 h** (16 `back`, 3 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
