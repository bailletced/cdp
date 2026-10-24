# F11 — E-mail de confirmation et fichier agenda · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 6 | 3 h |
| `test` | 2 | 1 h |
| **Total** | **8** | **4 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Ports et adaptateurs (US-11.1, US-11.2)

- [x] **F11-T01** · `back` Port `Notifier` (Chain/Application/Port) + adaptateur `MailerNotifier` (`TemplatedEmail`, transport `async_transactional`) + test avec transport en mémoire — *Dépend de* : F02-T13 · *Fini quand* : un e-mail de test est mis en file puis envoyé par le worker.
- [ ] **F11-T02** · `back` Port `CalendarFile` + `EluceoCalendarFile` : un `VEVENT` par inscription (UID `inscription-{uuid}@<domaine>`, `SEQUENCE` = version, DTSTART/DTEND UTC, SUMMARY, DESCRIPTION, `VALARM -PT10M`) + tests — *Dépend de* : F09-T04 · *Fini quand* : fichier valide (validateur iCalendar), cas du changement d'heure testé.
- [ ] **F11-T03** · `back` `InscriptionDigestFinder` (ReadModel) : créneaux d'un priant pour une édition (libellés « heure de Paris », titres, textes complets, pistes) + tests d'intégration — *Dépend de* : F09-T06, F06-T04 · *Fini quand* : une seule requête par priant, créneau sans texte géré.

## E-mail de confirmation (US-11.1, US-11.3)

- [ ] **F11-T04** · `back` Gabarit `emails/inscription_confirmation` (HTML + texte) : récapitulatif, textes complets, lien personnel, mention « heure de Paris » + test de rendu — *Dépend de* : F11-T03, F02-T13 · *Fini quand* : rendu figé en test (snapshot HTML et texte).
- [ ] **F11-T05** · `back` Listener `InscriptionsRegistered` → un seul e-mail par lot, `.ics` joint, lien via `PersonalLinkIssuer` ; rien sans e-mail (RG-NO-3) + tests fonctionnels — *Dépend de* : F11-T01, F11-T02, F11-T04, F02-T11 · *Fini quand* : 3 créneaux → 1 e-mail ; inscription sans e-mail → 0 (tests).

## Téléchargement (US-11.2)

- [ ] **F11-T06** · `back` `GET /api/me/calendar.ics` (priant vérifié, édition publique, `Content-Type: text/calendar`) + tests d'acceptation — *Dépend de* : F11-T02, F10-T04 · *Fini quand* : visiteur et pass seul → refus, priant vérifié → fichier.

## Vérifications

- [ ] **F11-T07** · `test` Rendu dans Gmail, Outlook et Apple Mail (iOS), poids de l'e-mail mesuré ; import du `.ics` dans Google Agenda et Apple Calendrier — *Dépend de* : F11-T05 · *Fini quand* : captures et poids notés dans la PR, anomalies corrigées.
- [ ] **F11-T08** · `test` E2E : inscription → e-mail dans Mailpit avec `.ics` joint et lien personnel qui ouvre `/mon-espace` — *Dépend de* : F11-T05, F02-T20, F09-T21 · *Fini quand* : parcours vert en CI.
