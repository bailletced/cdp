# Features — 24 heures de prière

**Communion de prière Notre-Dame du Mont-Carmel** · Découpage produit et plan de réalisation

| | |
|---|---|
| Sources | [Spécifications fonctionnelles v0.3](../specs-fonctionnelles.md) · [Spécifications techniques v0.2](../specs-techniques.md) |
| Date | 17 octobre 2026 (mise à jour après les arbitrages) |
| Rôle | Analyse Product Owner |
| Statut | Arbitré — voir le [journal des arbitrages](#6-journal-des-arbitrages) |
| 1re édition | **samedi 28/11/2026 20h → dimanche 29/11/2026 20h** (veillée d'entrée dans l'Avent), inscriptions ouvertes le **13/11** |

Chaque feature est un **incrément livrable et démontrable**, dans un dossier `NN-slug` contenant :

- `README.md` : le besoin (objectif, personas, user stories et critères d'acceptation, RG et décisions T couvertes, dépendances, hors périmètre, priorité, estimation) ;
- `taches.md` : les tâches ordonnées, d'environ 30 minutes chacune, tests compris.

---

## 1. Synthèse de l'analyse PO

### 1.1 Vision et valeur

Permettre à une communauté de prière, dont une partie est âgée, de former **une chaîne ininterrompue de 24 heures**, chacun priant chez soi un texte proposé, et de **reconduire** l'événement d'une année sur l'autre. La réussite se mesure simplement : **48 créneaux couverts sur 48**, des priants qui reçoivent leur texte et prient à la bonne heure, sans incident technique pendant les 24 heures.

| Objectif des specs (§1) | Features qui le servent |
|---|---|
| 1. S'inscrire très simplement | F08, F09, F10, F14, F15, F16 (inscription téléphonique) |
| 2. Éviter qu'un créneau reste vide | F08 (code couleur), F13 (bandeau, tirage, suggestion), F23 (relance), F16 (tableau de bord) |
| 3. Remettre à chacun son texte | F06, F07, F11 (e-mail avec texte complet), F12, F28 |
| 4. Faire vivre la chaîne pendant 24 h | F17, F18, F19, F20, F21, F22, F24 (rappel de la veille) |
| 5. Préparer, animer, reconduire | F04, F05, F06, F07, F16, F23, F29 |
| Socle transverse | F00, F01, F02, F03, F25, F26, F27 |

### 1.2 Personas

| Persona | Profil | Attentes clés | Points de vigilance |
|---|---|---|---|
| **Marie, 74 ans** — priante fidèle | Téléphone, peu à l'aise avec le numérique, prie souvent la nuit | Gros caractères, inscription sans mot de passe, texte facile à retrouver (ou à imprimer), rappel la veille | Lisibilité (≥ 18 px, AA), cibles ≥ 44 px, lien personnel qui « marche toujours » |
| **Thomas, 41 ans** — père de famille | À l'aise, pressé | Inscrire « Famille Martin » (un priant, un nom) sur plusieurs créneaux, agenda ; sa grand-mère sans e-mail est inscrite par téléphone | Un e-mail = un priant = un nom ; changement de nom dans Mon espace |
| **Claire, 29 ans** — visiteuse, à Montréal | Arrive par un lien partagé | Comprendre en 10 secondes, se voir proposer un créneau, voir l'heure chez elle | Heure locale, confidentialité, pas de fuite des noms aux visiteurs |
| **Anne** — organisatrice | Bénévole, prépare l'édition | Saisir 48 textes, programmer, relancer, inscrire par téléphone, exporter, modérer | Back-office simple, aperçu et confirmation avant tout envoi, quota Brevo |
| **Mainteneur technique** (astreinte) | Développeur, relit le code produit par Claude Code | Déployer sans risque, être alerté, savoir quoi faire à 3 h du matin | VPS unique, pas de préproduction, gel des déploiements, runbook |

### 1.3 Parcours clés

| # | Parcours | Persona | Étapes et features |
|---|---|---|---|
| P1 | **Découvrir et s'inscrire** | Claire, Marie | Accueil et grille (F08) → bandeau ou « Prendre un créneau vide » (F13) → formulaire (F09) → confirmation + suggestion (F09, F13) → lire le texte (F12) → e-mail avec texte, lien et `.ics` (F11) |
| P2 | **Revenir et gérer** | Thomas | E-mail → lien personnel (F02) → Mon espace : changer son nom, se désinscrire, agenda (F14) ; « Recevoir mon lien » sur un nouvel appareil (F02) |
| P3 | **Prier son créneau** | Marie | Rappel la veille (F24) → alarme de l'agenda (F11) → « Pendant mon créneau » (F21) → texte (F12, F28) → « J'ai prié » (F19) → mot de relais (F20) |
| P4 | **Préparer une édition** | Anne | Bibliothèque (F06) → édition (F05) → attribution (F07) → édition de test puis programmation (F27) → ouverture automatique (F05, F08) → inscriptions téléphoniques (F16) → relance (F23) |
| P5 | **Animer et veiller pendant les 24 h** | Anne, mainteneur | Tableau de bord et export (F16), « En ce moment » (F17), modération (F22), supervision et astreinte (F03, F27) |
| P6 | **Reconduire** | Anne, Thomas | Bilan (F17) → duplication → invitation → priant reconnu → historique (F29) |

### 1.4 Priorisation et périmètre de novembre

- **MVP** — indispensable à une première édition réussie.
- **Cible 1re édition** — **retenu pour novembre** (arbitrage Q20) : Mercure (F18), pointage (F19), mots de relais (F20), intentions (F21), modération (F22), version imprimable (F28). Si le calendrier glisse, ce sont les premières features à reporter, dans cet ordre : F28, puis F21 et F22 ensemble, puis F20 (avec F22 si F21 est déjà reportée), F19, F18. F22 est toujours livrée avec F20 ou F21.
- **Après 1re édition** — F29 (duplication, historique, invitations, désabonnement), sans valeur tant qu'aucune édition n'a eu lieu.

**Changement de modèle (Q7/Q10)** : un e-mail = un priant = un nom affiché ; plus de nombre de personnes ni d'inscription de groupe ; chaque inscription compte pour un. Impacts : F09, F10, F14, F16, F17, F19, F21, F25.

### 1.5 Chemin critique

**Chaîne de dépendances la plus longue** (calculée sur les tâches) : F00 (spike API Platform 5, Docker, noyau, horloge) → F05 (`ParisCalendar`, `Edition`) → F08 (planning) → F09 (inscription) → F11 (e-mail) → F14 (espace) → F26 (audit) → F27 (go/no-go).

**Chemin critique calendaire** : tout ce qui précède l'ouverture du **13/11** (lots 0 à 2, édition de test) tient en **3,5 semaines** ; la saisie des 48 textes par la Communion doit être finie le **08/11** ; le domaine neuf doit être chauffé avant le 13/11 ; les textes juridiques doivent être validés avant le 12/11.

### 1.6 Écarts assumés par rapport au plan du §15

| Écart | Raison |
|---|---|
| Squelette du lot 0 par « Recevoir mon lien » (F02) | Arbitrage Q4, T54 : pas de code jetable. |
| Gestion des organisateurs (F04) au lot 1, tableau de bord, gestion des inscriptions et export CSV (F16) au lot 2 | Nécessaires avant l'ouverture des inscriptions. |
| Port `ChangeSignal` et horloge pilotable e2e dans F17 (MVP) | Rend Mercure (F18) optionnel et permet de tester les 24 h. |
| Pas de préproduction : édition de test en production (S46) puis répétition générale en local / e2e (S48) | Arbitrage Q5, T52. Une partie de F27 est donc réalisée **avant** l'ouverture. |
| Reconduction (F29, contenu du lot 4) après la 1re édition | Aucune valeur sans édition précédente. |

---

## 2. Tableau des features

<!-- tableau -->
| N° | Feature | Priorité | Lot | Tâches | Heures |
|---|---|---|---|--:|--:|
| F00 | [Socle technique](00-socle-technique/README.md) · [tâches](00-socle-technique/taches.md) | MVP | 0 — Socle | 31 | 15,5 h |
| F01 | [Thème carmélitain et accessibilité de base](01-theme-accessibilite/README.md) · [tâches](01-theme-accessibilite/taches.md) | MVP | 0 — Socle | 13 | 6,5 h |
| F02 | [Accès par lien personnel (squelette de bout en bout)](02-acces-lien-personnel/README.md) · [tâches](02-acces-lien-personnel/taches.md) | MVP | 0 — Socle | 20 | 10 h |
| F03 | [Déploiement continu et supervision](03-deploiement-supervision/README.md) · [tâches](03-deploiement-supervision/taches.md) | MVP | 0 — Socle | 25 | 12,5 h |
| F04 | [Back-office : accès et organisateurs](04-back-office-organisateurs/README.md) · [tâches](04-back-office-organisateurs/taches.md) | MVP | 1 — Programme | 6 | 3 h |
| F05 | [Éditions et calendrier de Paris](05-editions-calendrier/README.md) · [tâches](05-editions-calendrier/taches.md) | MVP | 1 — Programme | 22 | 11 h |
| F06 | [Bibliothèque de textes](06-bibliotheque-textes/README.md) · [tâches](06-bibliotheque-textes/taches.md) | MVP | 1 — Programme | 16 | 8 h |
| F07 | [Attribution des textes aux créneaux](07-attribution-textes/README.md) · [tâches](07-attribution-textes/taches.md) | MVP | 1 — Programme | 12 | 6 h |
| F08 | [Planning public (page d'accueil)](08-planning-public/README.md) · [tâches](08-planning-public/taches.md) | MVP | 1 — Programme | 11 | 5,5 h |
| F09 | [Inscription sur un ou plusieurs créneaux](09-inscription/README.md) · [tâches](09-inscription/taches.md) | MVP | 2 — Participation | 21 | 10,5 h |
| F10 | [Pass appareil et politique de visibilité](10-pass-appareil-visibilite/README.md) · [tâches](10-pass-appareil-visibilite/taches.md) | MVP | 2 — Participation | 11 | 5,5 h |
| F11 | [E-mail de confirmation et fichier agenda (.ics)](11-email-confirmation-ics/README.md) · [tâches](11-email-confirmation-ics/taches.md) | MVP | 2 — Participation | 8 | 4 h |
| F12 | [Lecture des textes à méditer](12-lecture-textes/README.md) · [tâches](12-lecture-textes/taches.md) | MVP | 2 — Participation | 6 | 3 h |
| F13 | [Mise en avant des créneaux vides](13-creneaux-vides/README.md) · [tâches](13-creneaux-vides/taches.md) | MVP | 2 — Participation | 8 | 4 h |
| F14 | [Espace personnel](14-espace-personnel/README.md) · [tâches](14-espace-personnel/taches.md) | MVP | 2 — Participation | 11 | 5,5 h |
| F15 | [Protection anti-abus des formulaires publics](15-anti-abus/README.md) · [tâches](15-anti-abus/taches.md) | MVP | 2 — Participation | 6 | 3 h |
| F16 | [Pilotage des inscriptions (tableau de bord, gestion, export)](16-pilotage-inscriptions/README.md) · [tâches](16-pilotage-inscriptions/taches.md) | MVP | 2 — Participation | 15 | 7,5 h |
| F17 | [« En ce moment » et bilan](17-en-ce-moment-bilan/README.md) · [tâches](17-en-ce-moment-bilan/taches.md) | MVP | 3 — Les 24 heures | 10 | 5 h |
| F18 | [Temps réel (Mercure)](18-temps-reel-mercure/README.md) · [tâches](18-temps-reel-mercure/taches.md) | Cible 1re édition | 3 — Les 24 heures | 6 | 3 h |
| F19 | [Pointage « J'ai prié »](19-pointage/README.md) · [tâches](19-pointage/taches.md) | Cible 1re édition | 3 — Les 24 heures | 5 | 2,5 h |
| F20 | [Mots de relais](20-mots-de-relais/README.md) · [tâches](20-mots-de-relais/taches.md) | Cible 1re édition | 3 — Les 24 heures | 7 | 3,5 h |
| F21 | [Intentions de prière et « Pendant mon créneau »](21-intentions/README.md) · [tâches](21-intentions/taches.md) | Cible 1re édition | 3 — Les 24 heures | 11 | 5,5 h |
| F22 | [Modération des mots de relais et des intentions](22-moderation/README.md) · [tâches](22-moderation/taches.md) | Cible 1re édition | 3 — Les 24 heures | 4 | 2 h |
| F23 | [Campagnes d'e-mails : relance « créneaux vides », message libre et quota journalier](23-campagnes-email/README.md) · [tâches](23-campagnes-email/taches.md) | MVP | 4 — Communication | 15 | 7,5 h |
| F24 | [Rappel de la veille et tâches planifiées](24-rappel-veille-planification/README.md) · [tâches](24-rappel-veille-planification/taches.md) | MVP | 4 — Communication | 7 | 3,5 h |
| F25 | [Données personnelles : « Mes données » et confidentialité](25-rgpd-mes-donnees/README.md) · [tâches](25-rgpd-mes-donnees/taches.md) | MVP | 5 — Finitions | 10 | 5 h |
| F26 | [Audit d'accessibilité et tests avec des priants âgés](26-audit-accessibilite/README.md) · [tâches](26-audit-accessibilite/taches.md) | MVP | 5 — Finitions | 11 | 5,5 h |
| F27 | [Exploitation, édition de test et répétition générale](27-exploitation-repetition/README.md) · [tâches](27-exploitation-repetition/taches.md) | MVP | 5 — Finitions (une partie en S46, avant l'ouverture) | 17 | 8,5 h |
| F28 | [Version imprimable des textes](28-version-imprimable/README.md) · [tâches](28-version-imprimable/taches.md) | Cible 1re édition | 5 — Finitions | 3 | 1,5 h |
| F29 | [Reconduction : duplication, historique, invitations et désabonnement](29-reconduction-invitations/README.md) · [tâches](29-reconduction-invitations/taches.md) | Après 1re édition | 4 — Communication (réalisé après la 1re édition) | 13 | 6,5 h |
| | **Total** | | | **361** | **180,5 h** |
<!-- /tableau -->

## 3. Totaux

<!-- totaux -->
| Priorité | Features | Tâches | Heures |
|---|---|--:|--:|
| MVP | F00, F01, F02, F03, F04, F05, F06, F07, F08, F09, F10, F11, F12, F13, F14, F15, F16, F17, F23, F24, F25, F26, F27 | 312 | 156 h |
| Cible 1re édition | F18, F19, F20, F21, F22, F28 | 36 | 18 h |
| Après 1re édition | F29 | 13 | 6,5 h |
| **Total** | | **361** | **180,5 h** |

| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 184 | 92 h |
| `front` | 87 | 43,5 h |
| `infra` | 32 | 16 h |
| `test` | 45 | 22,5 h |
| `doc` | 9 | 4,5 h |
| `design` | 4 | 2 h |
<!-- /totaux -->

**Mesure de taille, pas de durée.** Les heures sont des **heures humaines** : une tâche vaut environ 30 minutes d'un développeur confirmé, tests compris. Elles mesurent la taille du travail. Les tâches sont implémentées par Claude Code (agents en parallèle) ; l'utilisateur relit et valide. **La cadence réelle dépend donc surtout du temps de relecture et de validation**, et du temps de la Communion (textes, validations, testeurs).

---

## 4. Rétroplanning de la 1re édition

### 4.1 Semaine par semaine

| Semaine | Contenu | Taille (tâches / h humaines) | Jalon |
|---|---|--:|---|
| **S43** — lun 19/10 → dim 25/10 | Lot 0 : F00, F01, F02, F03 (dont chauffe du domaine dès que Brevo est prêt) | 89 / 44,5 h | **J0 — lot 0 déployé** (dim 25/10) : squelette en production, déploiement à chaque fusion |
| **S44** — lun 26/10 → dim 01/11 | Lot 1 : F04, F05, F06, F07, F08 | 67 / 33,5 h | **J1 — programme prêt** (ven 30/10) : la Communion saisit les 48 textes |
| **S45** — lun 02/11 → dim 08/11 | Lot 2 : F09 à F16 ; F25-T07/T08 (confidentialité, consentement) | 88 / 44 h | 48 textes saisis et présentation fournie (dim 08/11) |
| **S46** — lun 09/11 → dim 15/11 | F27-T02 à T05 (édition de test, suppression, programmation), F26-T07/T08 (tests avec priants âgés) ; puis F17 et F23 | 31 / 15,5 h | Édition de test mar 10 → mer 11/11 · programmation jeu 12/11 · **J2 — ouverture des inscriptions ven 13/11** |
| **S47** — lun 16/11 → dim 22/11 | Lot 3 : F18 à F22 ; F24 (rappel), reste de F25, F28 | 51 / 25,5 h | Relance n° 1 vers le jeu 19/11 · **J3 — tout est en production** (dim 22/11) |
| **S48** — lun 23/11 → dim 29/11 | F26 (audit, corrections), reste de F27 (gel, tests transverses, procédures, répétition) | 22 / 11 h | Répétition mar 24–mer 25/11 · relance n° 2 mer 25/11 · **go/no-go jeu 26/11** · rappel ven 27/11 18h · **édition sam 28/11 20h → dim 29/11 20h** |
| Après | F29 (reconduction) | 13 / 6,5 h | Fin du pointage dim 06/12 |

Avant l'ouverture : **≈ 250 tâches (≈ 125 h humaines)** à produire **et relire** en 3,5 semaines. S43 et S45 sont les semaines les plus lourdes.

**Gel** : automatique dès que l'édition est « en cours » (28/11 20h, F27-T01). Recommandation : gel **manuel** dès le go/no-go du 26/11 (seuls les correctifs validés sont fusionnés).

### 4.2 Actions externes datées

| Quand | Qui | Action |
|---|---|---|
| **Semaine du 19/10** | Utilisateur | Acheter le domaine dédié ; créer le compte Brevo (validation du compte, authentification du domaine) ; créer le projet Hetzner Cloud (vérification d'identité, serveur CX32 ou CPX21, sauvegardes automatiques) ; créer le dépôt GitHub public ; ouvrir les comptes Sentry (UE) et de sonde externe |
| Semaine du 19/10 | Communion | Fournir la liste des organisateurs (e-mails) |
| Dès que Brevo est prêt (S43–S44) | Utilisateur | Vérifier que le suivi des clics est désactivable ; lancer la chauffe du domaine (F03-T25) |
| S44 (avant le 01/11) | Communion | Valider le brouillon de palette et de typographie (Claude) ; fournir la présentation de l'édition |
| S44–S45 (avant le 08/11) | Communion | Saisir les 48 textes dans la bibliothèque |
| Avant le 11/11 | Communion | Valider les brouillons juridiques (confidentialité, mentions légales, consentement) et les gabarits d'e-mails (Claude) |
| Avant le 10/11 | Communion | Recruter 5 testeurs, dont 3 priants de plus de 70 ans |
| 10–11/11 | Testeurs | Édition de test en production |
| 12/11 | Organisateurs | Programmer la vraie édition (ouverture 13/11) |
| 13/11 | Communion | Annoncer l'ouverture par ses canaux habituels |
| Avant le 22/11 | Communion | Désigner l'astreinte des 24 heures (au moins 2 personnes) |
| 19/11 et 25/11 | Organisateurs | Relances « créneaux vides » (éviter le 27/11, jour du rappel) |
| 26/11 | Utilisateur + Communion | Go/no-go, export CSV et `make backup` |
| 30/11 | Organisateurs | Message de remerciement et bilan |

### 4.3 Risques calendrier

1. **Relecture** : ≈ 45 h humaines de code à relire en S43 et en S45 ; si la relecture prend du retard, l'ouverture du 13/11 glisse. Parade : priorité absolue aux lots 0 à 2, report des features « Cible » dans l'ordre du §1.4.
2. **Démarches externes** : validation du compte Brevo, vérification d'identité Hetzner et propagation DNS peuvent prendre plusieurs jours, alors que tout le lot 0 en dépend.
3. **Domaine neuf** : moins de 3 semaines de chauffe avant les premières confirmations ; risque d'arrivée en indésirables.
4. **Contenus de la Communion** : 48 textes au 08/11, validations juridiques au 11/11. Repli : programmer avec des créneaux sans texte (RG-ED-4, RG-TX-2) ; le rappel de la veille contiendra les textes ajoutés ensuite.
5. **Pas de préproduction** : les features des lots 3 à 5 (« En ce moment », pointage, relais, intentions, rappel) ne tourneront en production qu'à partir du 28/11 ; seuls l'e2e avec horloge simulée et la répétition locale les éprouvent. Chaque fusion pendant les inscriptions ouvertes touche de vrais priants (garde-fous : CI verte obligatoire, e2e sur l'image, retour arrière Portainer).
6. **Astreinte** : un seul VPS et un seul mainteneur ; 24 heures de veille demandent au moins deux personnes.

---

## 5. Conventions

**Format d'une tâche** :
`- [ ] **F06-T03** · \`back\` Description — *Dépend de* : F00-T12 · *Fini quand* : critère vérifiable.`

Les identifiants `F<NN>-T<NN>` sont stables ; une dépendance pointe toujours vers une tâche antérieure (même feature ou feature de numéro inférieur). L'ordre des numéros suit les lots ; le rétroplanning (§4) précise les tâches réalisées en avance (F25-T07/T08, F26-T07/T08, F27-T02 à T05). Couches : `back`, `front`, `infra`, `test`, `doc`, `design`.

**Définition de « terminé »** (s'applique à toute tâche, en plus de son critère propre) :

- les tests de la couche concernée sont écrits et verts (pyramide du §14 : unitaires, fonctionnels InMemory, intégration et acceptation sur PostgreSQL, Vitest/MSW) ; les e2e Playwright font l'objet de tâches dédiées ;
- `make qa` est vert (Rector, PHP-CS-Fixer, PHPStan max, deptrac, Biome, `tsc`) ;
- si l'API change : `make openapi` régénéré, aucune dérive en CI ;
- si le schéma change : migration Doctrine réversible et compatible avec la version précédente du code (« on ajoute d'abord, on retire ensuite ») ; toute table rattachée à une édition porte une clé étrangère `ON DELETE CASCADE` (suppression d'une édition de test, F27-T02) ;
- e-mails : transport `async_transactional` (confirmation, lien, mise à jour, rappel) ou `async_bulk` (campagnes, soumis au quota journalier) ;
- aucune adresse e-mail en clair dans les journaux ; seules les ressources `/api/admin/*` exposent des e-mails ;
- interface : check-list d'accessibilité respectée ([docs/design/accessibilite.md](../design/accessibilite.md), F01-T13), textes en français, heures en heure de Paris, nom « Communion de prière Notre-Dame du Mont-Carmel ».

---

## 6. Journal des arbitrages

| # | Sujet | Décision | Features impactées |
|---|---|---|---|
| Q1 | Calendrier et capacité | 1re édition 28/11/2026 20h → 29/11 20h ; ouverture vers le 13/11 ; rappel le 27/11 à 18h. Claude Code implémente, l'utilisateur relit. MVP + « Cible » pour novembre, F29 ensuite. | Index (§4) |
| Q2 | Fournisseurs | Brevo (gratuit, 300/jour) ; domaine neuf dédié, chauffé ; Hetzner Cloud CX32/CPX21 avec sauvegardes automatiques ; dépôt GitHub public. | F03, F23, F27 |
| Q3 | Contenus | Nom « Communion de prière Notre-Dame du Mont-Carmel ». Claude rédige en brouillon les textes juridiques, la palette et la typographie, les gabarits d'e-mails ; la Communion fournit les 48 textes, la présentation, les organisateurs, l'astreinte. | F01, F02, F23, F25, F27 |
| Q4 | Squelette du lot 0 | Via « Recevoir mon lien » sur un compte créé par commande (T54). | F02 |
| Q5 | Préproduction | Aucune. Horloge pilotable en e2e seulement. Répétition en local / e2e, édition de test en production avant la programmation, supprimée par `app:edition:delete-test`. | F03, F17, F27 |
| Q6 | Consentement | Case toujours affichée et obligatoire hors session ; masquée en session si déjà donné ; date du premier consentement conservée. | F09 |
| Q7 / Q10 | Modèle | Un e-mail = un priant = un nom ; plus de nombre de personnes ; une inscription compte pour un ; priant sans e-mail possible (saisie organisateur) ; nom modifiable à tout moment en session ou par un organisateur ; hors session, le nom existant n'est jamais écrasé ; signature d'intention = nom du priant ou « Anonyme ». | F09, F10, F14, F16, F17, F19, F21, F25 |
| Q8 | Durée des liens | Hors édition et comptes organisateurs : 7 jours ; pendant une édition : fin + 7 jours ; session : 1 an. | F02 |
| Q9 | Lien d'invitation | Cible en liste blanche (`/`, `/mon-espace`) ; invitation → planning ; impossible tant que l'édition n'est pas ouverte. | F29 |
| Q11 | Export | Export CSV dans le back-office (`/api/admin/editions/{id}/inscriptions.csv` : nom, e-mail, créneaux), qui sert aussi au plan de secours (T55). | F16, F27 |
| Q12 | Comptages | Un créneau affiche son nombre d'inscriptions ; bilan = priants distincts. | F08, F09, F16, F17 |
| Q13 | Heure de début | Saisie en heure de Paris sur :00 ou :30, résolution automatique : heure ambiguë → première occurrence, inexistante → décalée d'une heure. | F05 |
| Q14 | Suppression d'un texte | Interdite si attribué à une édition programmée (quel que soit son statut) ; sinon autorisée, attributions retirées. | F06 |
| Q15 | Fichier agenda | Un seul `.ics` par édition (bouton unique + pièce jointe). | F11, F14 |
| Q16 | Révocation | Commande `app:account:revoke`, sans interface (T53). | F27 |
| Q17 | Effacement d'un organisateur-priant | Seul le priant est effacé ; le compte organisateur subsiste, accès révoqués. | F25 |
| Q18 | Accueil sans édition | Présentation sobre, « La prochaine édition sera bientôt annoncée », lien « Recevoir mon lien ». | F08 |
| Q19 | Organisateurs et tableau de bord | Lots 1 et 2 (F04, F16). | F04, F16 |
| Q20 | Features « Cible » | Incluses pour novembre. | F18–F22, F28 |
| Q21 | Reconduction | Après la 1re édition. | F29 |
| Q22 | ALTCHA | Conservé (désactivé par défaut). | F15 |
| Q23 | Déploiement | À chaque fusion sur `main` (T51) ; gel pendant une édition en cours ; garde-fous : CI verte, e2e sur l'image, retour arrière Portainer. | F03, F27 |
| Q24 | Volume | 50 à 150 priants ; offre gratuite Brevo ; transports `async_transactional` (prioritaire) et `async_bulk` ; quota journalier avec report des envois groupés au lendemain 8h (T56) ; test de charge dimensionné à 150 priants. | F00, F02, F03, F11, F23, F24, F27 |
| Q25 | Constantes | Rappel à 18h, alarme `.ics` 10 minutes avant : constantes en V1. | F11, F24 |

### Nouvelles questions

- **N1 — Suivi des clics Brevo.** Les liens personnels ne doivent pas être réécrits. Il faut vérifier, dès la création du compte (F03-T03), que l'offre gratuite permet de désactiver le suivi des clics sur les e-mails transactionnels, et qu'elle n'ajoute pas de mention publicitaire. À défaut : offre payante ou autre fournisseur, à décider la semaine du 19/10.
- **N2 — Liens des organisateurs-priants.** Avec la règle Q8, un organisateur qui est aussi priant reçoit dans ses e-mails de confirmation un lien valable 7 jours, et non jusqu'à la fin de l'édition + 7 jours. Est-ce acceptable ? *Proposition* : oui, il peut toujours demander un nouveau lien.
- **N3 — Fonctionnalités jamais vues en production avant le 28/11.** « En ce moment », pointage, relais, intentions et rappel de la veille ne seront éprouvés qu'en e2e et en répétition locale (risque n° 5). *Option* : avancer F17 en début de S46 pour que l'édition de test du 10–11/11 démarre son créneau en cours dans la soirée et montre « En ce moment » en production. Coût : environ 5 h humaines à relire avant le 10/11.
- **N4 — Gel manuel dès le go/no-go (26/11).** *Proposition* : à partir du 26/11, seuls les correctifs validés sont fusionnés ; le gel automatique prend le relais le 28/11 à 20h.
