# F25 — Données personnelles · Tâches

<!-- recap -->
| Couche | Tâches | Heures |
|---|--:|--:|
| `back` | 5 | 2,5 h |
| `front` | 2 | 1 h |
| `test` | 1 | 0,5 h |
| `doc` | 2 | 1 h |
| **Total** | **10** | **5 h** |

Chaque tâche vaut environ 30 minutes de travail d'un développeur confirmé, tests compris. Légende et définition de « terminé » : [index des features](../README.md).
<!-- /recap -->

## Effacement — back (US-25.1)

- [ ] **F25-T01** · `back` `Priant::erase()` : anonymisation sur place (nom « Un priant », e-mail, consentement et désabonnement effacés, `erasedAt`), identifiant conservé pour les statistiques + tests unitaires — *Dépend de* : F09-T02 · *Fini quand* : invariants testés, priant effacé non réutilisable.
- [ ] **F25-T02** · `back` Commande `ErasePriant` (une transaction : inscriptions à venir supprimées, passées conservées sous « Un priant », priant anonymisé) + événement d'intégration `PriantErased` + tests fonctionnels et d'intégration — *Dépend de* : F25-T01 · *Fini quand* : nombre de priants distincts inchangé dans le bilan d'une édition passée (test PostgreSQL).
- [ ] **F25-T03** · `back` `ErasePriant` supprime aussi mots de relais et intentions du priant (si F20 / F21 livrées) + tests — *Dépend de* : F25-T02, F20-T01, F21-T02 · *Fini quand* : plus aucun contenu du priant dans les fils (test).
- [ ] **F25-T04** · `back` Listener Access `PriantErased` : `credentialsVersion` + 1, passes, remember-me et sessions supprimés ; compte supprimé, sauf compte organisateur conservé (arbitrage Q17) + tests — *Dépend de* : F25-T02, F10-T01, F02-T09 · *Fini quand* : ancien lien → 401 ; un organisateur reste organisateur (tests d'acceptation).
- [ ] **F25-T05** · `back` `POST /api/me/erasure` (confirmation explicite dans le payload, déconnexion) + tests d'acceptation — *Dépend de* : F25-T04 · *Fini quand* : pass seul et visiteur refusés, priant vérifié déconnecté après succès.

## Effacement — front (US-25.1)

- [ ] **F25-T06** · `front` Section « Mes données » de Mon espace : explication, suppression à double confirmation, écran final sans session + tests — *Dépend de* : F25-T05, F14-T07 · *Fini quand* : impossible de supprimer par un seul clic (test).

## Information (US-25.2)

- [ ] **F25-T07** · `doc` Brouillon (rédigé par Claude) de la page « Confidentialité et mentions légales » et du libellé du consentement (responsable, finalités, base légale, conservation, droits, sous-traitants, contact, hébergeur) — *Dépend de* : F01-T11 · *Fini quand* : texte validé par la Communion.
- [ ] **F25-T08** · `front` Intégrer le contenu définitif de `/confidentialite` et le libellé validé du consentement dans le formulaire d'inscription — *Dépend de* : F25-T07, F09-T17 · *Fini quand* : page en ligne, lien depuis le pied de page et la case de consentement.
- [ ] **F25-T09** · `doc` Registre des traitements et note des écarts assumés (T20 conservation, T46 consentement, article 9 pour les intentions), liste des accords de sous-traitance — *Dépend de* : F25-T07 · *Fini quand* : documents dans `docs/rgpd/`, relus par la Communion.

## Parcours

- [ ] **F25-T10** · `test` E2E : suppression de mes données → session fermée, ancien lien refusé, inscriptions à venir supprimées, « Un priant » dans une édition passée — *Dépend de* : F25-T06 · *Fini quand* : parcours vert avec axe-core.
