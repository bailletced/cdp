# F23 — Campagnes d'e-mails et quota journalier · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 11 | 5,5 h |
| `front` | 3 | 1,5 h |
| `test` | 1 | 0,5 h |
| **Total** | **15** | **7,5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Moteur d'envoi (US-23.4)

- [ ] **F23-T01** · `back` Agrégat `Campaign` (Chain/Domain/Communication : type `empty_slots` / `free_message` / `invitation` / `eve_reminder`, objet, corps, auteur) + entité `Delivery` (destinataire figé, `pending` / `sent` / `failed`) + tests — *Dépend de* : F09-T02 · *Fini quand* : transitions de `Delivery` testées.
- [ ] **F23-T02** · `back` Repository InMemory + Doctrine, migrations `campaign` / `delivery` (FK édition `ON DELETE CASCADE`) + suite abstraite — *Dépend de* : F23-T01, F00-T19 · *Fini quand* : suite verte sur les deux implémentations.
- [ ] **F23-T03** · `back` `RecipientFinder` (ReadModel) pour `empty_slots` et `free_message` : priants de l'édition avec e-mail, non effacés + tests d'intégration — *Dépend de* : F09-T06 · *Fini quand* : priants sans e-mail exclus, un priant = un destinataire.

## Quota journalier (US-23.3)

- [ ] **F23-T04** · `back` Garde de quota `DailyEmailQuota` (port + adaptateur PostgreSQL) : compteur de tous les envois par jour de Paris, limite (300) et réserve transactionnelle configurables, incrément dans `MailerNotifier` + tests — *Dépend de* : F11-T01, F05-T01 · *Fini quand* : bascule de jour à minuit heure de Paris testée, compteur partagé entre processus.
- [ ] **F23-T05** · `back` Commande `SendCampaign` : crée la campagne, fige les `Delivery`, un `DeliverCampaignMessage(deliveryId)` par destinataire sur `async_bulk` + tests fonctionnels — *Dépend de* : F23-T02, F23-T03 · *Fini quand* : N destinataires → N messages sur `async_bulk` (test).
- [ ] **F23-T06** · `back` Handler `DeliverCampaignMessage` idempotent (n'envoie que si `pending`, puis `sent`), lien personnel généré par destinataire + tests — *Dépend de* : F23-T05, F02-T11, F11-T01 · *Fini quand* : message rejoué → aucun second envoi (test).
- [ ] **F23-T07** · `back` Quota appliqué aux envois groupés : au-delà de « limite − réserve », report au lendemain 8h (heure de Paris) par `DelayStamp` ; débit limité (10/s) ; `failed` après épuisement des essais + tests — *Dépend de* : F23-T06, F23-T04 · *Fini quand* : report et débit testés avec `MockClock`, transactionnel jamais retardé.

## Gabarits et API (US-23.1 à US-23.3)

- [ ] **F23-T08** · `back` Gabarit « Créneaux vides » (brouillon Claude, validé par la Communion) : introduction modifiable, créneaux vides à venir avec liens `/inscription?creneaux=…`, lien personnel + test de rendu — *Dépend de* : F23-T06, F13-T02 · *Fini quand* : heures avec mention « heure de Paris », rendu figé en test.
- [ ] **F23-T09** · `back` Gabarit « Message libre » (objet, corps en `RichText`, lien personnel) + test de rendu — *Dépend de* : F23-T06 · *Fini quand* : HTML nettoyé, version texte générée.
- [ ] **F23-T10** · `back` `POST /api/admin/campaigns/preview` : rendu, nombre de destinataires, étalement prévu jour par jour, avertissement si un rappel de la veille tombe dans la fenêtre + tests d'acceptation — *Dépend de* : F23-T07, F23-T08, F23-T09, F04-T02 · *Fini quand* : aucun envoi déclenché par l'aperçu, étalement exact (test).
- [ ] **F23-T11** · `back` `POST /api/admin/campaigns` (envoi) et `GET /api/admin/campaigns` (historique : envoyés / en attente / reportés / en échec) + tests d'acceptation — *Dépend de* : F23-T10 · *Fini quand* : OpenAPI régénéré, avancement exact.

## Écrans (US-23.1 à US-23.4)

- [ ] **F23-T12** · `front` Écran « Envois » : historique et avancement (rafraîchi pendant l'envoi, reports affichés) — *Dépend de* : F23-T11, F04-T04 · *Fini quand* : test Vitest/MSW, tableau accessible.
- [ ] **F23-T13** · `front` Rédiger un envoi : type (relance ou message libre), introduction ou objet + corps (`RichTextEditor`), erreurs 422 + tests — *Dépend de* : F23-T12, F06-T10 · *Fini quand* : les deux types testés.
- [ ] **F23-T14** · `front` Aperçu (rendu, destinataires, étalement « X aujourd'hui, Y demain », avertissement rappel) puis confirmation explicite + tests — *Dépend de* : F23-T13 · *Fini quand* : envoi impossible sans passer par l'aperçu (test).

## Parcours

- [ ] **F23-T15** · `test` E2E : relance « créneaux vides » → e-mails dans Mailpit avec liens directs fonctionnels ; quota réduit à 3 → reste reporté puis envoyé le lendemain 8h (horloge pilotable) ; rejeu → pas de doublon — *Dépend de* : F23-T14, F17-T05 · *Fini quand* : parcours vert (§14.1 « parcours organisateur »).
