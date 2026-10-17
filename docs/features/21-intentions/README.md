# F21 — Intentions de prière et « Pendant mon créneau »

| Lot | Priorité | Estimation |
|---|---|---|
| 3 — Les 24 heures | Cible 1re édition | <!-- est -->11 tâches · 5,5 h<!-- /est --> |

## Objectif et valeur

Permettre aux inscrits (session ouverte) de **confier une intention à la chaîne** (500 caractères, signée ou anonyme), publiée immédiatement et visible de tous les inscrits ; et offrir, dans l'espace personnel, un mode **« Pendant mon créneau »** qui met en avant le texte à méditer, le mot de relais du créneau précédent et les intentions confiées.

Valeur : les intentions donnent un contenu commun à la prière de toute la chaîne ; le mode « Pendant mon créneau » réunit en un écran tout ce dont le priant a besoin au moment de prier (notamment la nuit, sur téléphone).

## Personas concernés

- **Thomas, 41 ans** : confie une intention pour un ami malade, en restant anonyme et sans le nommer.
- **Marie, 74 ans** : à 3h00, ouvre son espace et trouve son texte, le mot de la personne précédente et les intentions.
- **Anne, organisatrice** : peut supprimer une intention problématique (→ F22).

## User stories

### US-21.1 — Confier une intention
En tant que **priant vérifié inscrit**, je veux déposer une intention afin que toute la chaîne prie pour elle.

- **Étant donné** une session ouverte et une inscription dans l'édition, **quand** je dépose une intention de 300 caractères en choisissant « Anonyme », **alors** elle est publiée immédiatement, signée « Anonyme ».
- **Étant donné** une intention signée de mon nom, **quand** je change ensuite mon nom dans Mon espace, **alors** l'intention affiche mon nouveau nom (signature = nom du priant ou « Anonyme », arbitrage Q7).
- **Étant donné** le formulaire, **quand** je l'ouvre, **alors** un avertissement m'invite à ne pas donner d'information permettant d'identifier un tiers (nom de famille, détails de santé).
- **Étant donné** un appareil avec pass mais sans session, ou un priant sans inscription dans l'édition, **quand** il tente de déposer, **alors** c'est refusé (RG-LV-5).
- **Étant donné** l'édition terminée, **quand** je tente de déposer, **alors** c'est refusé.

### US-21.2 — Lire et retirer les intentions
En tant qu'**inscrit**, je veux lire les « Intentions confiées à la chaîne », les plus récentes en tête, et retirer les miennes.

- **Étant donné** un inscrit, **quand** il ouvre `/intentions`, **alors** il voit les intentions, les plus récentes en premier ; un visiteur non inscrit est refusé.
- **Étant donné** mon intention, **quand** je la supprime, **alors** elle disparaît pour tous.

### US-21.3 — Pendant mon créneau
En tant que **Marie**, je veux qu'à l'heure de mon créneau mon espace mette en avant ce qui m'est utile pour prier.

- **Étant donné** mon créneau en cours, **quand** j'ouvre `/mon-espace`, **alors** je vois en premier mon texte, le ou les mots de relais du créneau précédent et les intentions confiées à la chaîne.
- **Étant donné** la fin de mon créneau, **quand** le temps passe, **alors** l'espace revient à son affichage normal et me propose d'écrire mon mot de relais.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : RG-LV-5, RG-LV-6, RG-LV-7, RG-LV-4 (mise en avant pour le créneau suivant) ; §5.5 « Pendant mon créneau » ; §5.10 (données sensibles, avertissement).
- **Décisions techniques** : §4.3 (`Intention`), §4.4 (`PostIntention`, `DeleteIntention`), §4.8 (signal `intentions`), §5.2.

## Dépendances

- F14 (espace), F10 (politique), F12 (texte), F17 (`ChangeSignal`, horloge e2e), F20 (mots de relais pour « Pendant mon créneau »).

## Hors périmètre

- Intentions déposées par des visiteurs non inscrits, modération préalable (hors périmètre V1).
- Suppression par un organisateur (→ F22).

## Estimation

<!-- est -->**11 tâches × 0,5 h = 5,5 h** (7 `back`, 3 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
