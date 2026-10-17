# F09 — Inscription sur un ou plusieurs créneaux

| Lot | Priorité | Estimation |
|---|---|---|
| 2 — Participation | MVP | <!-- est -->21 tâches · 10,5 h<!-- /est --> |

## Objectif et valeur

Permettre à chacun de s'inscrire **très simplement** (2 écrans : choix des créneaux, formulaire) sur un ou plusieurs créneaux, sans mot de passe ni validation préalable de l'e-mail. C'est l'objectif n° 1 de l'application.

Modèle retenu (arbitrages Q7/Q10, specs fonctionnelles v0.3) : **un e-mail = un priant = un nom affiché**. Une famille s'inscrit comme un seul priant (« Famille Dupont ») ; il n'y a plus de nombre de personnes : **chaque inscription compte pour un** (RG-PL-1 = nombre d'inscriptions). Le nom est porté par le `Priant`, pas par l'inscription.

La feature crée les agrégats `Priant` et `Inscription`, garantit l'unicité (un priant, une inscription par créneau) jusqu'en base, recueille le consentement RGPD et met à jour les compteurs de la grille.

## Personas concernés

- **Marie, 74 ans** : s'inscrit seule sur un créneau de nuit depuis son téléphone.
- **Thomas, 41 ans** : inscrit « Famille Martin » sur trois créneaux. Sa grand-mère, sans e-mail, est inscrite par téléphone par une organisatrice (F16).
- **Claire, visiteuse** : s'inscrit pour la première fois et donne son consentement.

## User stories

### US-09.1 — M'inscrire en deux écrans
En tant que **Marie**, je veux choisir mes créneaux puis remplir un formulaire court (nom affiché, e-mail, consentement) afin d'être inscrite sans difficulté.

- **Étant donné** deux créneaux à venir sélectionnés, **quand** je valide un formulaire correct, **alors** mes deux inscriptions sont enregistrées immédiatement et j'arrive sur l'écran de confirmation.
- **Étant donné** un nom vide ou de plus de 80 caractères, **quand** je valide, **alors** l'erreur s'affiche sur le champ du nom.

### US-09.2 — Un nom par priant
En tant que **Thomas**, je veux inscrire ma famille sous un seul nom afin qu'elle apparaisse ainsi dans la chaîne.

- **Étant donné** le nom « Famille Martin » et mon e-mail, **quand** je m'inscris sur trois créneaux, **alors** les trois créneaux affichent « Famille Martin » et comptent chacun **un** inscrit de plus (RG-PL-1).
- **Étant donné** mon e-mail déjà connu sous « Famille Martin » et aucune session, **quand** je m'inscris en saisissant « Thomas Martin », **alors** l'inscription est enregistrée sous « Famille Martin » : le nom saisi n'écrase jamais un nom existant (RG-IN-2) ; seul « Mon espace » (F14) ou un organisateur (F16) peut le changer.

### US-09.3 — Une inscription par créneau
En tant que **priant**, je ne peux avoir qu'une inscription par créneau.

- **Étant donné** mon e-mail déjà inscrit à 3h00, **quand** je m'y inscris de nouveau (ou par double clic), **alors** je lis « Vous êtes déjà inscrit sur ce créneau » et rien n'est créé (409).

### US-09.4 — Consentement et reconnaissance
En tant que **priant**, je veux un formulaire qui me reconnaît afin d'aller plus vite, sans que personne n'apprenne si une adresse est connue.

- **Étant donné** aucune session, **quand** j'ouvre le formulaire, **alors** la case de consentement est toujours affichée et obligatoire ; si l'adresse avait déjà consenti, la date du **premier** consentement est conservée (arbitrage Q6).
- **Étant donné** une session ouverte, **quand** j'ouvre le formulaire, **alors** mon nom et mon e-mail sont pré-remplis (RG-IN-7) et la case est masquée si j'ai déjà consenti.

### US-09.5 — Respecter le temps de l'édition
En tant qu'**organisatrice**, je veux que l'on ne puisse s'inscrire que pendant la période prévue afin de garder une chaîne cohérente.

- **Étant donné** l'édition en cours, **quand** je m'inscris sur le créneau en cours, **alors** c'est accepté ; **sur un créneau passé**, c'est refusé.
- **Étant donné** une édition terminée ou non encore ouverte, **quand** je tente de m'inscrire, **alors** c'est refusé.
- **Étant donné** une première inscription sur une édition, **quand** une organisatrice tente de modifier l'heure de début, **alors** c'est refusé (RG-ED-3).

### US-09.6 — Confirmation
En tant que **priant**, je veux un écran de confirmation récapitulant mes créneaux afin d'être rassuré.

- **Étant donné** une inscription réussie, **quand** l'écran s'affiche, **alors** je vois le récapitulatif des créneaux et « Un e-mail de confirmation vous a été envoyé ».

## Règles de gestion et décisions couvertes

- **Règles de gestion** : §5.4 (parcours), RG-IN-1 (enregistrement immédiat ; l'e-mail est en F11), RG-IN-2 (v0.3 : un e-mail = un priant = un nom, nom existant conservé hors session), RG-IN-3, RG-IN-4, RG-IN-7, RG-ED-3, RG-PL-1 (nombre d'inscriptions), RG-PL-2 ; §5.10 (consentement, date du premier consentement).
- **Décisions techniques** : T6, T10 ; §4.3 (`Priant` avec `email?` et `displayName`, `Inscription` avec `priantId` obligatoire), §4.4 (flux d'une inscription), §4.5 (`UNIQUE (slot_id, priant_id)`, unique partiel sur `lower(email)`), §4.8, §8.4.

## Dépendances

- F05, F07, F08 (édition, créneaux, grille), F02 (comptes Access), F01 (formulaires).
- Complétée par F10 (pass appareil), F11 (e-mail), F12 (texte), F13 (suggestion), F14 (changer son nom), F15 (limitation), F16 (inscription par un organisateur).

## Hors périmètre

- Nombre de personnes et inscription de groupe (supprimés, arbitrages Q7/Q10).
- Changement de nom (→ F14 pour le priant, F16 pour un organisateur).
- Validation préalable de l'e-mail (exclue par RG-IN-1), plafond par créneau ou par priant.

## Estimation

<!-- est -->**21 tâches × 0,5 h = 10,5 h** (15 `back`, 5 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
