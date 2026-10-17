# F22 — Modération des mots de relais et des intentions

| Lot | Priorité | Estimation |
|---|---|---|
| 3 — Les 24 heures | Cible 1re édition | <!-- est -->4 tâches · 2 h<!-- /est --> |

> Livrée **en même temps** que F20 ou F21 : on ne publie pas de contenu libre sans moyen de le retirer.

## Objectif et valeur

Les mots de relais et les intentions sont publiés **sans modération préalable**. Les organisateurs doivent donc pouvoir, a posteriori, lister et supprimer un contenu inapproprié ou trop personnel (par exemple une intention qui nomme un tiers et décrit sa maladie).

Valeur : condition de confiance et de conformité (les intentions peuvent contenir des données de santé). Cette feature n'a de sens qu'avec F20 et/ou F21, et doit être livrée **en même temps** qu'elles.

## Personas concernés

- **Anne, organisatrice** : surveille le fil pendant les 24 heures et supprime une intention qui cite nommément un malade.

## User stories

### US-22.1 — Lister les contenus publiés
En tant qu'**organisatrice**, je veux la liste des mots de relais et des intentions de l'édition, avec leur auteur, afin de les relire.

- **Étant donné** l'édition en cours, **quand** j'ouvre « Modération », **alors** je vois deux onglets (mots de relais, intentions), les plus récents en tête, avec le nom affiché et l'e-mail de l'auteur.

### US-22.2 — Supprimer un contenu
En tant qu'**organisatrice**, je veux supprimer un mot de relais ou une intention afin de retirer un contenu inapproprié.

- **Étant donné** une intention, **quand** je confirme sa suppression, **alors** elle disparaît pour tous les inscrits (au prochain rafraîchissement, ou aussitôt avec Mercure).

## Règles de gestion et décisions couvertes

- **Règles de gestion** : RG-LV-4 (suppression d'un mot de relais par un organisateur), RG-LV-7 (suppression d'une intention par un organisateur) ; §5.9 module 5 ; §5.10 (données sensibles).
- **Décisions techniques** : T17 (e-mails seulement sous `/api/admin`) ; §5.2 (`relay-messages`, `intentions` sous `/api/admin`).

## Dépendances

- F20 (mots de relais), F21 (intentions), F04 (back-office).

## Hors périmètre

- Modération préalable, filtrage automatique de mots (exclus par les specs).
- Notification de l'auteur lors d'une suppression.

## Estimation

<!-- est -->**4 tâches × 0,5 h = 2 h** (2 `back`, 1 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
