# F26 — Audit d'accessibilité et tests avec des priants âgés

| Lot | Priorité | Estimation |
|---|---|---|
| 5 — Finitions | MVP | <!-- est -->11 tâches · 5,5 h<!-- /est --> |

## Objectif et valeur

Vérifier, avant la première édition, que l'application est réellement utilisable par son public : contrôle axe-core étendu à toutes les routes, audit manuel aux lecteurs d'écran **VoiceOver** (iOS) et **NVDA** (Windows), contrôles manuels (zoom, clavier, paysage), et **tests avec trois priants âgés** sur leur propre téléphone. Un budget de corrections est réservé.

Valeur : les automates ne détectent qu'une partie des problèmes ; l'objectif « inscription en 2 écrans, utilisable par une personne âgée » ne se vérifie qu'en observant de vrais utilisateurs.

## Personas concernés

- **Marie, 74 ans** : testeuse ; s'inscrit et lit son texte sur son téléphone.
- **Utilisateur de lecteur d'écran** : réalise le parcours d'inscription sans voir l'écran.
- **Anne, organisatrice** : utilise le back-office au clavier.

## User stories

### US-26.1 — Aucune violation automatique
En tant que **mainteneur**, je veux qu'axe-core contrôle toutes les routes publiques, l'espace personnel et le back-office afin de garantir un socle sans régression.

- **Étant donné** la suite e2e, **quand** elle parcourt chaque route, **alors** aucune violation axe-core n'est tolérée.

### US-26.2 — Parcours au lecteur d'écran
En tant qu'**utilisateur de VoiceOver ou NVDA**, je veux réaliser l'inscription, lire mon texte et gérer mes créneaux afin de prier comme tout le monde.

- **Étant donné** VoiceOver sur iPhone, **quand** je m'inscris sur un créneau vide, **alors** j'y parviens sans aide, chaque état de créneau étant annoncé (« À pourvoir », nombre de priants).

### US-26.3 — Validation par de vrais priants
En tant que **responsable de la Communion**, je veux que trois priants âgés testent l'application afin de valider qu'elle est assez simple.

- **Étant donné** trois testeurs de plus de 70 ans, **quand** ils réalisent l'inscription et la lecture du texte sur leur téléphone, pendant l'édition de test en production (10–11/11, avant l'ouverture), **alors** au moins deux sur trois y parviennent seuls, et les difficultés observées sont corrigées.

## Règles de gestion et décisions couvertes

- **Règles de gestion** : §6 « Public âgé, accessibilité » (≥ 18 px, AA, grandes zones tactiles, jamais la couleur seule, inscription en 2 écrans) ; §5.3-1.
- **Décisions techniques** : T32 ; §8.5 (axe-core bloquant, vérification manuelle VoiceOver et NVDA avant la première édition).

## Dépendances

- Toutes les features d'interface MVP (F01, F08 à F17, F23, F25), idéalement aussi F19 à F22 si elles sont livrées.

## Hors périmètre

- Audit RGAA complet et déclaration de conformité officielle (non obligatoires pour une association).
- Tests sur des lecteurs d'écran autres que VoiceOver et NVDA.

## Estimation

<!-- est -->**11 tâches × 0,5 h = 5,5 h** (3 `front`, 8 `test`). Détail dans [taches.md](taches.md).<!-- /est -->
