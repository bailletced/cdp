# F16 — Pilotage des inscriptions · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 10 | 5 h |
| `front` | 4 | 2 h |
| `test` | 1 | 0,5 h |
| **Total** | **15** | **7,5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Tableau de bord (US-16.1)

- [ ] **F16-T01** · `back` `DashboardViewFinder` : statut, créneaux vides, inscriptions, priants distincts, pointages + tests d'intégration — *Dépend de* : F09-T13 · *Fini quand* : indicateurs vérifiés sur le jeu de fixtures.
- [ ] **F16-T02** · `back` `GET /api/admin/editions/{id}/dashboard` + tests d'acceptation — *Dépend de* : F16-T01, F05-T14 · *Fini quand* : réservé aux organisateurs, OpenAPI régénéré.

## Gestion — back (US-16.2 à US-16.5)

- [ ] **F16-T03** · `back` `AdminInscriptionViewFinder` + `GET /api/admin/inscriptions?edition=&slot=` (nom, e-mail, origine, pointage) + tests — *Dépend de* : F09-T06, F04-T02 · *Fini quand* : filtres testés, e-mails présents uniquement ici.
- [ ] **F16-T04** · `back` `AdminPriantViewFinder` + `GET /api/admin/priants?q=` (recherche par nom ou e-mail) et `PATCH /api/admin/priants/{id}` (nom, via `RenamePriant`) + tests d'acceptation — *Dépend de* : F14-T03, F04-T02, F09-T03 · *Fini quand* : recherche insensible aux accents, priant refusé (403).
- [ ] **F16-T05** · `back` Commande `RegisterInscriptionsForSomeone` (priant existant, ou nouveau priant avec e-mail facultatif ; sans consentement — T46 ; origine `organizer` ; mêmes règles temporelles) + tests fonctionnels — *Dépend de* : F09-T08 · *Fini quand* : nouveau priant sans e-mail créé, priant existant réutilisé (tests).
- [ ] **F16-T06** · `back` `POST /api/admin/inscriptions` (confirmation par le listener de F11 si e-mail, aucun pass appareil) + tests d'acceptation — *Dépend de* : F16-T05, F11-T05 · *Fini quand* : e-mail mis en file seulement avec adresse, aucun `Set-Cookie: cdp_pass`.
- [ ] **F16-T07** · `back` `DELETE /api/admin/inscriptions/{id}` (réutilise `CancelInscription`, sans fenêtre horaire pour l'organisateur) + tests — *Dépend de* : F16-T03, F14-T05 · *Fini quand* : priant refusé (403), organisateur accepté.
- [ ] **F16-T08** · `back` Commande `MoveInscription` (unicité dans le créneau cible, version incrémentée) + événement `InscriptionMoved` + tests fonctionnels — *Dépend de* : F09-T06 · *Fini quand* : doublon dans le créneau cible refusé (test).
- [ ] **F16-T09** · `back` `POST /api/admin/inscriptions/{id}/move` + gabarit « Mise à jour de votre inscription » avec `.ics` (même UID, `SEQUENCE` + 1) + tests — *Dépend de* : F16-T08, F11-T02, F11-T04 · *Fini quand* : 409 sur doublon, e-mail de mise à jour testé.
- [ ] **F16-T10** · `back` Export `GET /api/admin/editions/{id}/inscriptions.csv` (nom, e-mail, créneaux en heure de Paris ; UTF-8 avec BOM, séparateur `;` pour les tableurs) + tests d'acceptation — *Dépend de* : F16-T03 · *Fini quand* : fichier ouvert correctement dans LibreOffice et Excel, refusé hors organisateur.

## Écrans (US-16.1 à US-16.5)

- [ ] **F16-T11** · `front` Écran « Tableau de bord » : indicateurs, créneaux vides cliquables (vers la liste filtrée) — *Dépend de* : F16-T02, F04-T04 · *Fini quand* : test Vitest/MSW.
- [ ] **F16-T12** · `front` Écran « Inscriptions » : liste par créneau (nom, e-mail, origine, pointé), filtre par créneau, bouton « Exporter (CSV) » — *Dépend de* : F16-T03, F16-T10, F04-T04 · *Fini quand* : test Vitest/MSW, tableau accessible (en-têtes de colonnes).
- [ ] **F16-T13** · `front` « Inscrire quelqu'un » : recherche d'un priant existant ou saisie d'un nouveau (nom, e-mail facultatif), créneaux, aide « pas de consentement recueilli » + tests — *Dépend de* : F16-T04, F16-T06, F16-T12 · *Fini quand* : priant existant, nouveau avec et sans e-mail testés.
- [ ] **F16-T14** · `front` Renommer le priant, supprimer, déplacer (choix du créneau cible, message 409) + tests — *Dépend de* : F16-T04, F16-T07, F16-T09, F16-T12 · *Fini quand* : trois actions testées.

## Parcours

- [ ] **F16-T15** · `test` E2E : inscription téléphonique sans e-mail ; déplacement d'une inscription avec e-mail → e-mail de mise à jour dans Mailpit ; export CSV téléchargé — *Dépend de* : F16-T13, F16-T14 · *Fini quand* : parcours vert avec axe-core.
