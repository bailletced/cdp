# F15 — Protection anti-abus des formulaires publics

| Lot | Priorité | Estimation |
|---|---|---|
| 2 — Participation | MVP | <!-- est -->6 tâches · 3 h<!-- /est --> |

## Objectif et valeur

Protéger les deux formulaires ouverts à tous (inscription, « Recevoir mon lien ») contre les robots et les envois en masse, **sans gêner** un public âgé (pas de captcha visuel par défaut) : champ piège, limitation par IP et par e-mail partagée entre réplicas, réponse identique qui ne révèle rien, et un défi ALTCHA auto-hébergé **prêt à activer** en cas d'attaque.

Valeur : préserver la réputation du domaine d'envoi (un robot qui inscrit des milliers d'adresses ruinerait la délivrabilité des liens personnels) et la fiabilité du planning.

## Personas concernés

- **Anne, organisatrice** : ne veut pas d'inscriptions fantaisistes dans la grille.
- **Mainteneur d'astreinte** : peut durcir la protection en une variable d'environnement.
- **Marie, 74 ans** : ne doit jamais être bloquée par une protection invisible pour elle.

## User stories

### US-15.1 — Neutraliser les robots simples
En tant qu'**organisatrice**, je veux un champ piège invisible afin que les robots qui remplissent tout soient ignorés.

- **Étant donné** le champ `website` rempli, **quand** le formulaire est envoyé, **alors** la réponse est identique à un succès mais rien n'est enregistré ni envoyé.

### US-15.2 — Limiter les envois
En tant que **mainteneur**, je veux limiter les inscriptions (10/h par IP, 5/h par e-mail) et les demandes de lien (10/h par IP, 3/h par e-mail) afin d'éviter les abus.

- **Étant donné** une 11e inscription en une heure depuis la même IP, **quand** elle est envoyée, **alors** l'API répond 429 avec `Retry-After` et le formulaire affiche un message clair et poli.
- **Étant donné** une 4e demande de lien en une heure pour la même adresse, **quand** elle est envoyée, **alors** la réponse reste 202 (aucune fuite), mais aucun e-mail ne part.
- **Étant donné** deux réplicas de l'application, **quand** les requêtes se répartissent entre eux, **alors** les compteurs sont partagés (PostgreSQL) ; l'IP prise en compte est l'IP réelle du client derrière Traefik.

### US-15.3 — Durcir en cas d'attaque
En tant que **mainteneur d'astreinte**, je veux activer un défi ALTCHA par variable d'environnement afin de bloquer une attaque sans redéployer de code.

- **Étant donné** `ALTCHA_ENABLED=1`, **quand** un visiteur envoie un formulaire, **alors** un défi est résolu automatiquement par son navigateur et vérifié côté serveur ; sans solution valide, la requête est refusée.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : exigence §6 « Sécurité » (champ piège, limitation du nombre d'envois) ; §5.5 (message identique adresse connue ou non).
- **Décisions techniques** : T18 ; §6.7.

## Dépendances

- F09 (`POST /api/inscriptions`), F02 (`POST /auth/link-request`), F03 (Traefik, pour l'IP réelle).

## Hors périmètre

- Captcha tiers (reCAPTCHA, hCaptcha) : exclus (vie privée, tiers).
- Protection contre une attaque volumétrique (DDoS) : relève de l'hébergeur.
- Purge des anciens compteurs (→ F24).

## Estimation

<!-- est -->**6 tâches × 0,5 h = 3 h** (5 `back`, 1 `front`). Détail dans [taches.md](taches.md).<!-- /est -->
