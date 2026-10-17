# F20 — Mots de relais

| Lot | Priorité | Estimation |
|---|---|---|
| 3 — Les 24 heures | Cible 1re édition | <!-- est -->7 tâches · 3,5 h<!-- /est --> |

## Objectif et valeur

Permettre à chaque priant de laisser, à partir du début de son créneau, un **court message à la chaîne** (500 caractères au plus), publié immédiatement et lisible par tous les inscrits dans le **fil du relais**, classé dans l'ordre des créneaux. Le mot est aussi mis en avant pour les priants du créneau suivant (voir F21, « Pendant mon créneau »).

Valeur : c'est ce qui transforme une suite de créneaux en **chaîne** : on reçoit le relais de quelqu'un et on le passe. Non indispensable à une première édition, d'où la priorité « Cible ».

## Personas concernés

- **Marie, 74 ans** : laisse « Je confie à la chaîne la paix dans nos familles » à la fin de son créneau.
- **Thomas, 41 ans** : lit le mot de Marie en commençant le sien.
- **Anne, organisatrice** : peut supprimer un mot inapproprié (→ F22).

## User stories

### US-20.1 — Laisser un mot de relais
En tant que **priant vérifié**, je veux écrire un mot de relais pour une inscription afin de passer le relais à la chaîne.

- **Étant donné** mon créneau commencé, **quand** j'écris un mot de 480 caractères, **alors** il est publié immédiatement, sans modération préalable.
- **Étant donné** 501 caractères, **quand** j'envoie, **alors** une erreur indique la limite ; un compteur m'aide pendant la saisie.
- **Étant donné** mon mot publié, **quand** je le modifie ou le supprime avant la fin de l'édition, **alors** le fil est mis à jour.
- **Étant donné** mon créneau pas encore commencé, ou l'édition terminée, **quand** j'ouvre mon espace, **alors** l'écriture n'est pas proposée (RG-LV-3).

### US-20.2 — Lire le fil du relais
En tant qu'**inscrit**, je veux lire les mots de relais dans l'ordre des créneaux afin de suivre la chaîne.

- **Étant donné** un inscrit (pass ou session), **quand** il ouvre `/relais`, **alors** il voit les mots classés par créneau, avec le nom affiché et l'horaire.
- **Étant donné** un visiteur non inscrit, **quand** il ouvre `/relais`, **alors** l'accès est refusé avec une invitation à s'inscrire.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : RG-LV-3, RG-LV-4 (publication immédiate, fil du relais, suppression par l'auteur ; la suppression par un organisateur est en F22) ; §4 (« Lire le fil du relais »).
- **Décisions techniques** : §4.3 (`RelayMessage` dans `Inscription`), §4.4 (`WriteRelayMessage`, `DeleteRelayMessage`), §4.8 (`RelayMessageWritten` → signal `relay`), §5.2.

## Dépendances

- F14 (espace), F10 (voter, règle « inscrit de l'édition » de la `VisibilityPolicy`), F17 (port `ChangeSignal`, horloge e2e).

## Hors périmètre

- Mise en avant pour le créneau suivant (→ F21, « Pendant mon créneau »).
- Modération préalable (exclue par les specs).
- Mise en forme du mot (texte brut uniquement).

## Estimation

<!-- est -->**7 tâches × 0,5 h = 3,5 h** (4 `back`, 2 `front`, 1 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
