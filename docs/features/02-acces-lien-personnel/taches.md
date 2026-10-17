# F02 — Accès par lien personnel · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 16 | 8 h |
| `front` | 3 | 1,5 h |
| `test` | 1 | 0,5 h |
| **Total** | **20** | **10 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Comptes et organisateurs (US-02.5)

- [x] **F02-T01** · `back` Access/Domain : agrégat `Account` (`AccountId`, `Email`, `credentialsVersion`, `PriantId?`) + tests unitaires — *Dépend de* : F00-T12 · *Fini quand* : incrément de `credentialsVersion` testé.
- [x] **F02-T02** · `back` `AccountRepositoryInterface` + InMemory + Doctrine, migration `account` (unique sur `lower(email)`) + suite abstraite `AccountRepositoryTestCase` — *Dépend de* : F02-T01, F00-T19 · *Fini quand* : la suite passe sur InMemory et PostgreSQL.
- [x] **F02-T03** · `back` Agrégat `Organizer` (`AccountId`, ajouté par, ajouté le) + repository InMemory/Doctrine + migration + tests — *Dépend de* : F02-T02 · *Fini quand* : suite abstraite verte sur les deux implémentations.
- [x] **F02-T04** · `back` Console `app:organizer:create <email>` (Access/Infrastructure/Console) : crée `Account` + `Organizer`, idempotente — *Dépend de* : F02-T03 · *Fini quand* : test de commande (création puis relance sans doublon).

## Sécurité et session (US-02.2, US-02.3, US-02.4)

- [x] **F02-T05** · `back` `SecurityUser` + `UserProvider` : chargement par UUID d'`Account`, rôles `ROLE_PRIANT` / `ROLE_ORGANIZER` recalculés à chaque rafraîchissement + tests — *Dépend de* : F02-T03 · *Fini quand* : un organisateur retiré perd son rôle à la requête suivante (test).
- [x] **F02-T06** · `back` Firewall avec état, sessions PostgreSQL (`PdoSessionHandler`, migration), cookies `__Host-`, `Secure`, `HttpOnly`, `SameSite=Lax` — *Dépend de* : F02-T05 · *Fini quand* : session partagée entre deux processus PHP (test d'acceptation).
- [x] **F02-T07** · `back` `login_link` : `signature_properties: [id, credentialsVersion]`, `check_post_only: true`, `GET /auth/connexion` redirige vers la SPA `/connexion` + tests — *Dépend de* : F02-T06 · *Fini quand* : un GET n'ouvre aucune session (test).
- [ ] **F02-T08** · `back` `POST /auth/connexion` : ouvre la session et renvoie la cible ; lien expiré, altéré ou révoqué → 401 typé + tests d'acceptation — *Dépend de* : F02-T07 · *Fini quand* : les quatre cas sont testés.
- [ ] **F02-T09** · `back` `remember_me` (`always_remember_me`, 1 an, `token_provider: doctrine`, migration) — *Dépend de* : F02-T08 · *Fini quand* : test « session supprimée → toujours authentifié par remember-me ».
- [ ] **F02-T10** · `back` `POST /auth/logout` + vérification `Origin`/`Sec-Fetch-Site` et CSRF same-origin sur toute requête non sûre — *Dépend de* : F02-T09 · *Fini quand* : déconnexion testée ; POST d'une autre origine → 403.
- [ ] **F02-T11** · `back` Port `PersonalLinkIssuer` (Shared/Application/Port) + adaptateur `login_link` : pendant une édition (de l'ouverture à la fin + 7 j) durée = fin + 7 j, sinon 7 j ; toujours 7 j pour un compte organisateur — *Dépend de* : F02-T07 · *Fini quand* : les trois cas testés avec `MockClock`.
- [ ] **F02-T12** · `back` VO `Viewer` (Shared/Domain/Security : `priantId?`, `verified`, `coveredInscriptionIds[]`, `isOrganizer`) + port `CurrentViewer` + `SymfonyCurrentViewer` (session seule) + tests — *Dépend de* : F02-T05 · *Fini quand* : visiteur, priant vérifié et organisateur produisent le bon `Viewer`.

## Lien par e-mail (US-02.1)

- [ ] **F02-T13** · `back` Gabarit Twig de base `emails/layout.html.twig` (inky, CSS en ligne, couleurs, pied de page ; brouillon rédigé par Claude, validé par la Communion) ; expéditeur = nom de la Communion (paramètre) — *Dépend de* : F00-T18, F01-T01 · *Fini quand* : test de rendu HTML et texte brut.
- [ ] **F02-T14** · `back` Commande `RequestPersonalLink` + handler : compte connu → lien + e-mail « Votre lien personnel » via `async_transactional` ; inconnu → rien + tests fonctionnels — *Dépend de* : F02-T11, F02-T13 · *Fini quand* : les deux cas testés, aucun e-mail en clair dans les journaux.
- [ ] **F02-T15** · `back` `POST /auth/link-request` (payload e-mail + champ piège `website`) : toujours 202, sans écart de temps de réponse + tests d'acceptation — *Dépend de* : F02-T14 · *Fini quand* : réponses identiques pour adresse connue, inconnue et piège rempli.
- [ ] **F02-T16** · `back` `GET /api/me` minimal (e-mail, `isOrganizer`) ; 401 pour un visiteur + tests — *Dépend de* : F02-T12 · *Fini quand* : schéma OpenAPI régénéré, tests par profil.

## Écrans (US-02.1 à US-02.4)

- [ ] **F02-T17** · `front` Page `/recevoir-mon-lien` : formulaire e-mail, champ piège caché, message identique adresse connue ou non + tests — *Dépend de* : F02-T15, F01-T09 · *Fini quand* : tests Vitest/MSW, aucun indice sur l'existence du compte.
- [ ] **F02-T18** · `front` Page `/connexion` : POST automatique, états « en cours », « lien expiré → recevoir un nouveau lien », redirection vers la cible + tests — *Dépend de* : F02-T08, F01-T08 · *Fini quand* : les trois états testés.
- [ ] **F02-T19** · `front` Hook `useViewer`, garde de route, `/mon-espace` squelette avec « Me déconnecter » ; en-tête « Mon espace » ou « Recevoir mon lien » selon la session — *Dépend de* : F02-T16, F02-T10, F01-T07 · *Fini quand* : visiteur redirigé vers `/recevoir-mon-lien` (test).
- [ ] **F02-T20** · `test` E2E squelette : `app:organizer:create` → demande de lien → Mailpit → `/connexion` → `/mon-espace` → rechargement → déconnexion — *Dépend de* : F02-T17, F02-T18, F02-T19, F02-T04 · *Fini quand* : parcours vert en CI.
