# Spécifications fonctionnelles — 24 heures de prière

**Communion de prière Notre-Dame du Mont Carmel**

| | |
|---|---|
| Version | 0.2 — brouillon (avec les arbitrages du cadrage technique) |
| Date | 17 octobre 2026 |
| Auteur | Cédric Baillet |
| Statut | À valider par les responsables de la Communion |

---

## 1. Contexte et objectifs

Les « 24 heures de prière » sont un événement de la Communion de prière Notre-Dame du Mont Carmel : pendant 24 heures d'affilée, des priants se relaient par créneaux de 30 minutes pour former une **chaîne de prière ininterrompue**. Chacun prie **chez soi** et médite un texte proposé par les organisateurs pour son créneau.

L'application web doit :

1. permettre à chacun de s'inscrire très simplement sur un ou plusieurs créneaux ;
2. éviter qu'un créneau reste vide, en mettant en avant ceux qui le sont ;
3. remettre à chaque priant le texte à méditer de son créneau ;
4. faire vivre la chaîne pendant les 24 heures (créneau en cours, pointage, mots de relais, intentions) ;
5. donner aux organisateurs les outils pour préparer, animer et reconduire l'événement d'une année sur l'autre.

---

## 2. Glossaire

| Terme | Définition |
|---|---|
| **Édition** | Une occurrence de l'événement (ex. « 24 heures de prière — Carême 2027 »). |
| **Créneau** | Tranche de 30 minutes d'une édition. Une édition compte 48 créneaux. |
| **Priant** | Personne identifiée par son adresse e-mail. Un priant gère une ou plusieurs inscriptions. |
| **Inscription** | Engagement à prier sur un créneau : un nom affiché, un nombre de personnes, un e-mail de contact. |
| **Inscription de groupe** | Inscription dont le nombre de personnes est supérieur à 1 (ex. « Famille Martin · 4 »). |
| **Texte** | Texte à méditer, issu de la bibliothèque, attribué à un créneau. |
| **Lien personnel** | Lien secret envoyé par e-mail, qui ouvre l'espace personnel sans mot de passe (« lien magique »). |
| **Inscrit** | Visiteur ayant au moins une inscription dans l'édition en cours. |
| **Pointage** | Indication « J'ai prié » donnée par le priant pour une inscription. |
| **Mot de relais** | Court message laissé à la chaîne par un priant à la fin de son créneau. |
| **Intention** | Intention de prière confiée à la chaîne par un inscrit. |
| **Organisateur** | Membre de l'équipe qui administre l'application. |

---

## 3. Principes structurants

Ces choix conditionnent l'ensemble des spécifications (détail en [annexe A](#annexe-a--journal-des-décisions)).

- **Prière à distance** : chacun prie chez soi ; aucune logistique de lieu, aucune capacité maximale.
- **Heure de Paris comme référence** pour tout l'événement.
- **Plusieurs éditions, un seul groupe** : l'application est réutilisable d'une année sur l'autre ; pas de multi-groupes.
- **Pas de mot de passe** : identification par e-mail et lien personnel.
- **Ni objectif ni plafond par créneau** : un créneau est soit *vide* (0 priant), soit *occupé* (1 priant ou plus). Un groupe compte pour sa taille.
- **Un texte par créneau** : tous les priants d'un même créneau méditent le même texte ; les 48 textes forment une progression.
- **Les noms sont visibles des seuls inscrits**, en nom complet.
- **Les données sont conservées** d'une édition à l'autre, avec le consentement du priant, pour pouvoir le réinviter.

---

## 4. Acteurs et droits

| Action | Visiteur | Inscrit | Organisateur |
|---|:-:|:-:|:-:|
| Voir le planning et le nombre de priants par créneau | ✓ | ✓ | ✓ |
| Voir le titre et la source du texte de chaque créneau | ✓ | ✓ | ✓ |
| S'inscrire sur un ou plusieurs créneaux | ✓ | ✓ | ✓ (pour autrui) |
| Voir les noms des priants inscrits et leurs pointages | — | ✓ | ✓ |
| Lire le texte complet d'un créneau | — | ses créneaux | ✓ |
| Lire le fil du relais et les intentions | — | ✓ | ✓ |
| Pointer, laisser un mot de relais | — | ses inscriptions | — |
| Déposer une intention | — | ✓ | — |
| Administrer (éditions, textes, inscriptions, modération, communication) | — | — | ✓ |

> Un organisateur qui veut prier s'inscrit comme tout le monde.

---

## 5. Fonctionnalités

### 5.1 Éditions

**Attributs d'une édition**

- Titre
- Présentation : texte libre, avec l'intention générale et le thème de l'édition
- Date et heure de début (heure de Paris)
- Durée d'un créneau : 30 minutes, fixe
- Date d'ouverture des inscriptions

**Cycle de vie**

| Statut | Passage | Ce qui est possible |
|---|---|---|
| Brouillon | À la création | Visible des seuls organisateurs. Tout est modifiable. |
| Programmée | Action « Programmer » d'un organisateur, quand l'édition est prête | Invisible du public jusqu'à la date d'ouverture. Retour au brouillon possible. |
| Inscriptions ouvertes | Automatique à la date d'ouverture, pour une édition programmée ; immédiat si la date est déjà passée | Planning public, inscriptions, désinscriptions, dépôt d'intentions. |
| En cours | Automatique à l'heure de début | Mise en avant du créneau en cours, pointage, mots de relais. Les inscriptions restent possibles sur le créneau en cours et les créneaux à venir. |
| Terminée | Automatique à l'heure de fin | Lecture seule : planning, bilan, fil du relais, intentions. Le pointage reste possible 7 jours. |

**Règles de gestion**

- **RG-ED-1** — Une seule édition est publique à la fois : la plus récente parmi les éditions programmées dont la date d'ouverture est atteinte. Un brouillon n'est jamais public. Les éditions précédentes restent consultables par les organisateurs, et par chaque priant dans son historique.
- **RG-ED-2** — Les 24 heures sont découpées en 48 créneaux de 30 minutes, alignés sur l'heure de début. Celle-ci tombe sur une heure pleine ou une demi-heure.
- **RG-ED-3** — L'heure de début n'est plus modifiable dès la première inscription.
- **RG-ED-4** — Programmer une édition dont certains créneaux n'ont pas encore de texte est possible, mais un avertissement s'affiche.
- **RG-ED-5** — Changement d'heure : les créneaux correspondent à des durées réelles de 30 minutes. Si l'édition chevauche un changement d'heure, les libellés ambigus sont précisés, par exemple « 2h00 (heure d'été) » et « 2h00 (heure d'hiver) ».
- **RG-ED-6** — Un organisateur peut créer une édition en dupliquant la précédente : la présentation et l'attribution des textes sont reprises, sans les inscriptions.

### 5.2 Planning (page d'accueil)

**Contenu de la page**

1. En-tête : nom de la Communion, titre et dates de l'édition, présentation.
2. Bandeau des créneaux vides et bouton « Prendre un créneau vide » (voir 5.3).
3. Pendant l'événement : bloc « En ce moment » (voir 5.8).
4. La grille des créneaux.
5. Accès à « Mon espace » ou à « Recevoir mon lien personnel ».

**La grille des créneaux**

- Les créneaux sont présentés dans l'ordre chronologique, regroupés par moment de la journée : soirée, nuit, matin, après-midi.
- Chaque créneau affiche :
  - son horaire ;
  - son état (vide ou occupé) ;
  - le nombre de priants ;
  - le titre et la source du texte ;
  - pour les inscrits seulement : le nom des priants, une coche pour ceux qui ont pointé, et une mention « vous » sur leurs propres créneaux.
- On peut sélectionner plusieurs créneaux pour s'y inscrire en une seule fois.
- Les créneaux passés sont grisés et ne sont plus sélectionnables.

**Règles de gestion**

- **RG-PL-1** — Le nombre de priants d'un créneau est la somme des nombres de personnes de ses inscriptions.
- **RG-PL-2** — Un créneau est *vide* s'il n'a aucune inscription, *occupé* sinon. Il n'y a pas d'autre seuil.
- **RG-PL-3** — Les horaires sont affichés en heure de Paris. Si le navigateur est réglé sur un autre fuseau, l'heure locale s'affiche en complément, par exemple « 3h00 (21h00 chez vous) ».
- **RG-PL-4** — Les noms affichés sont les noms complets saisis à l'inscription. Ils ne sont jamais visibles d'un visiteur non inscrit.

### 5.3 Mise en avant des créneaux vides

Quatre mécanismes dans l'application, plus une relance par e-mail :

1. **Code couleur dans la grille** : les créneaux vides ressortent visuellement et portent le libellé « À pourvoir ». L'information ne passe jamais par la seule couleur.
2. **Bandeau** : « Il reste *X* créneaux vides sur 48 », suivi des horaires concernés, cliquables. Pendant l'événement, seuls les créneaux à venir sont comptés. Quand il n'y a plus de créneau vide, le bandeau affiche « La chaîne est complète. Merci ! ».
3. **Bouton « Prendre un créneau vide »** : tire au hasard un créneau vide à venir (le hasard répartit les priants), en affiche l'horaire et le titre du texte, puis propose « Je le prends » ou « Un autre ». Le bouton est masqué s'il n'y a plus de créneau vide.
4. **Suggestion après inscription** : sur l'écran de confirmation, s'il reste des créneaux vides, l'application en propose un. Elle choisit en priorité un créneau vide qui précède ou suit immédiatement celui qu'on vient de prendre, sinon un créneau au hasard. Elle propose « Le prendre aussi » (un clic, avec les mêmes informations) ou « Non merci ».
5. **Relance par e-mail** déclenchée par un organisateur (voir 5.9).

### 5.4 Inscription

**Parcours (2 écrans)**

1. **Choix du ou des créneaux** depuis la grille, le bandeau ou le bouton « Prendre un créneau vide ».
2. **Formulaire** :
   - nombre de personnes (1 par défaut) ;
   - nom affiché : le libellé est « Prénom et nom » pour une personne, « Nom du groupe » au-delà (ex. « Famille Martin ») ;
   - adresse e-mail ;
   - case de consentement obligatoire : conservation des données et invitation aux éditions suivantes (voir 5.10).

   Le formulaire est pré-rempli si le priant est déjà reconnu.
3. **Écran de confirmation** : récapitulatif des créneaux, accès au texte de chaque créneau, suggestion d'un créneau vide, et rappel « Un e-mail de confirmation vous a été envoyé ».

**Règles de gestion**

- **RG-IN-1** — L'inscription est enregistrée immédiatement, sans validation préalable de l'e-mail. Un e-mail de confirmation est envoyé.
- **RG-IN-2** — Un e-mail correspond à un priant. Un même e-mail peut porter des inscriptions sous des noms différents : un petit-fils peut, par exemple, inscrire sa grand-mère avec sa propre adresse.
- **RG-IN-3** — Un e-mail ne peut avoir qu'une inscription par créneau. Pour plusieurs personnes sur un même créneau, on augmente le nombre de personnes.
- **RG-IN-4** — Un priant peut prendre autant de créneaux qu'il le souhaite, consécutifs ou non.
- **RG-IN-5** — Le nombre de personnes est un entier, de 1 à une valeur maximale raisonnable (ex. 30) pour éviter les erreurs de saisie.
- **RG-IN-6** — Après une inscription, l'appareil bénéficie de la **vue inscrit** en lecture : noms, pointages, fil du relais, intentions, textes de ses créneaux. Les actions faites au nom du priant exigent une session ouverte par le lien personnel : modifier ou annuler une inscription, pointer, écrire un mot de relais, déposer une intention, consulter l'historique, supprimer ses données. Cela évite qu'une personne saisissant l'e-mail d'un autre puisse agir à sa place.
- **RG-IN-7** — Priant reconnu : un priant qui ouvre un lien personnel ou un lien d'invitation est reconnu sur cet appareil, et ses informations (nom affiché, nombre de personnes) sont pré-remplies. Le lien d'invitation est un lien personnel : il ouvre une session.

### 5.5 Espace personnel

**Accès**

- Par le lien personnel, présent dans chaque e-mail envoyé au priant.
- Par « Recevoir mon lien personnel » : le visiteur saisit son e-mail et reçoit un lien si l'adresse est connue. Le message affiché est le même que l'adresse soit connue ou non.
- Une fois le lien ouvert, la session est conservée durablement sur l'appareil.

**Contenu**

- **Mes créneaux** dans l'édition en cours. Pour chacun :
  - horaire, nom affiché et nombre de personnes ;
  - accès au texte et à sa version imprimable ;
  - actions : modifier le nom ou le nombre de personnes, se désinscrire, ajouter à mon agenda (fichier .ics), pointer, écrire le mot de relais.
- **Ajouter des créneaux.**
- **Déposer une intention.**
- **Pendant mon créneau** : l'espace met en avant le texte, le mot de relais laissé par le créneau précédent et les intentions confiées à la chaîne.
- **Historique** : mes créneaux et leurs textes dans les éditions précédentes.
- **Mes données** : se désabonner des invitations, supprimer toutes ses données.

**Règles de gestion**

- **RG-EP-1** — Le priant peut se désinscrire librement tant que le créneau n'a pas commencé. Aucune alerte n'est envoyée aux organisateurs. Si le créneau redevient vide, il réapparaît dans la mise en avant (5.3).
- **RG-EP-2** — Un lien personnel doit être impossible à deviner. Les e-mails ne sont jamais affichés aux autres priants.
- **RG-EP-3** — Supprimer ses données annule les inscriptions à venir. Dans les éditions passées, les inscriptions sont anonymisées (« Un priant ») pour conserver les statistiques.

### 5.6 Textes à méditer

**Bibliothèque** (commune à toutes les éditions)

Fiche d'un texte :

| Champ | Obligatoire | Description |
|---|:-:|---|
| Titre | ✓ | Ex. « Le château de l'âme » |
| Source | ✓ | Auteur, œuvre, référence. Ex. « Sainte Thérèse d'Avila, *Le Château intérieur*, I, 1 » |
| Corps | ✓ | Texte mis en forme simplement : paragraphes, gras, italique, citations, retours à la ligne (pour les psaumes) |
| Pistes de méditation | — | Questions ou prière finale proposées pour guider la demi-heure |

**Attribution aux créneaux**

- Chaque créneau d'une édition reçoit un texte de la bibliothèque.
- Fonction « Répartir dans l'ordre » : l'organisateur ordonne une sélection de textes, qui sont attribués successivement aux créneaux.
- Un même texte peut être attribué à plusieurs créneaux ; l'organisateur en est averti.

**Version imprimable** : mise en page A4 sobre, en gros caractères, avec l'horaire du créneau et le nom de la Communion. Elle peut être imprimée ou enregistrée en PDF.

**Règles de gestion**

- **RG-TX-1** — Le titre et la source sont visibles de tous dans le planning. Le texte complet et les pistes de méditation ne sont visibles que des priants inscrits sur ce créneau, dès leur inscription, et des organisateurs.
- **RG-TX-2** — Si un créneau n'a pas encore de texte, le message « Le texte de ce créneau sera bientôt disponible » s'affiche.
- **RG-TX-3** — Une modification dans la bibliothèque s'applique partout où le texte est attribué. Un texte attribué à une édition publiée ne peut pas être supprimé.

### 5.7 Notifications par e-mail

| E-mail | Destinataires | Déclenchement | Contenu |
|---|---|---|---|
| Confirmation d'inscription | Le priant | À chaque inscription (un seul e-mail si plusieurs créneaux sont pris d'un coup) | Récapitulatif des créneaux, texte complet de chaque créneau, lien personnel, fichier .ics avec alarme |
| Rappel la veille | Tous les priants de l'édition | La veille du début de l'édition, à 18h (heure de Paris) | Récapitulatif de tous ses créneaux et de leurs textes, conseil de mettre un réveil pour la nuit, lien personnel |
| Lien personnel | Le demandeur | « Recevoir mon lien personnel » | Lien personnel |
| Relance « créneaux vides » | Tous les priants de l'édition | Manuel, par un organisateur | Liste des créneaux vides générée automatiquement, avec liens directs ; message d'introduction modifiable |
| Message libre | Tous les priants de l'édition | Manuel, par un organisateur | Objet et corps rédigés par l'organisateur |
| Invitation à une nouvelle édition | Priants des éditions précédentes, consentants, non désabonnés et pas encore inscrits | Manuel, par un organisateur | Présentation de l'édition, lien vers le planning (priant reconnu), lien de désabonnement |

**Pas d'e-mail** pour : une désinscription (confirmation à l'écran uniquement), une alerte aux organisateurs en cas de désistement, un rappel juste avant le créneau.

**Règles de gestion**

- **RG-NO-1** — L'expéditeur porte le nom de la Communion. Toutes les heures sont exprimées en heure de Paris, avec la mention « heure de Paris ».
- **RG-NO-2** — Le fichier .ics contient un événement par créneau, en heure absolue, pour que l'agenda du priant convertisse correctement. Il comporte une alarme 10 minutes avant le début. Il est aussi téléchargeable depuis l'espace personnel.
- **RG-NO-3** — Une inscription sans e-mail (créée par un organisateur) ne reçoit aucune notification.

### 5.8 Pendant les 24 heures

**Bloc « En ce moment »** (page d'accueil)

- Horaire du créneau en cours.
- Nombre de priants (visiteur) ou noms des priants (inscrit).
- Barre de progression de la chaîne : créneaux écoulés sur 48 et temps restant.
- Créneau suivant : « Ensuite : 3h30–4h00 ».
- Si le créneau en cours est vide : « Personne n'est inscrit en ce moment — rejoignez la chaîne », avec un bouton d'inscription immédiate.
- Après la fin de l'édition, ce bloc devient un **bilan** : nombre de créneaux couverts sur 48, nombre de priants, remerciements.

**Pointage « J'ai prié »**

- **RG-LV-1** — Le pointage se fait depuis l'espace personnel, pour chaque inscription. Il est possible du début du créneau jusqu'à 7 jours après la fin de l'édition, et il est annulable.
- **RG-LV-2** — Le pointage est visible de tous les inscrits (coche à côté du nom) et des organisateurs. Une inscription de groupe a un seul pointage.

**Mot de relais**

- **RG-LV-3** — Un mot de relais par inscription, de 500 caractères au plus. On peut l'écrire du début du créneau jusqu'à la fin de l'édition ; son auteur peut le modifier ou le supprimer.
- **RG-LV-4** — Le mot de relais est publié immédiatement, sans modération préalable. Il est visible de tous les inscrits dans le **fil du relais**, classé dans l'ordre des créneaux. Il est aussi mis en avant dans l'espace des priants du créneau suivant. Un organisateur peut le supprimer.

**Intentions de prière**

- **RG-LV-5** — Seuls les inscrits, avec une session ouverte par le lien personnel, peuvent déposer une intention. C'est possible de l'ouverture des inscriptions à la fin de l'édition.
- **RG-LV-6** — Une intention fait 500 caractères au plus. Elle est signée du nom affiché ou « Anonyme », au choix. Le formulaire invite à ne pas donner d'informations permettant d'identifier un tiers (nom de famille, détails de santé).
- **RG-LV-7** — L'intention est publiée immédiatement. Elle est visible de tous les inscrits dans la liste « Intentions confiées à la chaîne » (les plus récentes en tête) et dans l'espace personnel pendant son créneau. Son auteur et les organisateurs peuvent la supprimer.

### 5.9 Espace organisateurs

**Accès**

- Une liste d'adresses e-mail d'organisateurs, qui se connectent par lien personnel.
- Tous les organisateurs ont les mêmes droits. Un organisateur peut en ajouter ou en retirer un autre, mais il doit toujours rester au moins un organisateur.

**Modules**

1. **Tableau de bord** de l'édition courante : statut, liste des créneaux vides, nombre d'inscriptions, nombre de personnes, nombre de priants distincts, pointages.
2. **Éditions** : créer (éventuellement par duplication), modifier, programmer ou repasser en brouillon, consulter les éditions terminées.
3. **Textes** : gérer la bibliothèque, attribuer les textes aux créneaux, répartir dans l'ordre.
4. **Inscriptions** :
   - liste par créneau, avec le nom, le nombre de personnes et l'e-mail ;
   - inscrire quelqu'un (par exemple reçu par téléphone), l'e-mail étant facultatif. Aucun consentement spécifique n'est recueilli dans ce cas : c'est un choix assumé (voir 5.10) ;
   - modifier le nom ou le nombre de personnes, supprimer une inscription ;
   - déplacer une inscription vers un autre créneau. Si l'inscription a un e-mail, le priant reçoit une confirmation mise à jour lors d'un ajout ou d'un déplacement.
5. **Modération** : liste des mots de relais et des intentions, avec suppression.
6. **Communication** : relance « créneaux vides », message libre, invitation à une nouvelle édition. Avant chaque envoi : aperçu, nombre de destinataires et confirmation. Un historique des envois est conservé.
7. **Organisateurs** : gestion de la liste.

### 5.10 Données personnelles (RGPD)

- **Données traitées** : e-mail, nom affiché, nombre de personnes, inscriptions, pointages, mots de relais, intentions.
- **Base légale** : consentement, recueilli par une case obligatoire lors de la première inscription. La date du consentement est conservée. *Exception assumée* : une inscription saisie par un organisateur avec e-mail ne fait pas l'objet d'un recueil de consentement.
- **Durée de conservation** : les données sont conservées sans limite de durée, sans purge automatique, pour permettre les réinvitations. Ce choix de la Communion a été confirmé lors du cadrage technique ; c'est un *écart assumé* par rapport au RGPD (voir le point ouvert n° 2).
- **Visibilité** : les noms ne sont visibles que des inscrits de l'édition ; les e-mails, que des organisateurs.
- **Droits des personnes** :
  - suppression de ses données en libre-service (RG-EP-3) ;
  - désabonnement des invitations ;
  - contact des organisateurs pour l'accès et la rectification.
- **Intentions** : elles peuvent contenir des données sensibles (santé). D'où l'avertissement du formulaire (RG-LV-6).
- Une page **« Confidentialité et mentions légales »** est accessible depuis toutes les pages.

---

## 6. Exigences non fonctionnelles

| Domaine | Exigence |
|---|---|
| Supports | Conçu d'abord pour le téléphone, pleinement utilisable sur ordinateur. |
| Public âgé, accessibilité | Texte de taille généreuse (corps ≥ 18 px), contrastes conformes au niveau AA, grandes zones tactiles, jamais d'information portée par la couleur seule, inscription en 2 écrans. |
| Langue | Français uniquement. |
| Identité visuelle | Esprit carmélitain : sobre, tons brun et crème, beaucoup de blanc, typographie très lisible. Nom affiché : « Communion de prière Notre-Dame du Mont Carmel ». |
| Fuseau horaire | Europe/Paris comme référence (RG-PL-3, RG-ED-5). |
| Disponibilité | Service critique pendant les 24 heures de l'édition. |
| E-mails | Bonne délivrabilité : domaine d'envoi authentifié (SPF, DKIM, DMARC). Ce sont les e-mails qui portent le lien personnel. |
| Sécurité | Liens personnels impossibles à deviner. Protection anti-spam des formulaires (champ piège, limitation du nombre d'envois). |

---

## 7. Hors périmètre (V1)

Ces sujets ont été écartés lors du cadrage :

- Prière sur place, gestion d'un lieu et capacité maximale par créneau
- Objectif de priants par créneau ; plafond par créneau ou par priant
- Plusieurs groupes ou communautés
- Comptes avec mot de passe ; rôles distincts entre organisateurs
- Rappel juste avant le créneau ; SMS, WhatsApp, notifications push
- Alerte aux organisateurs en cas de désistement
- Planning imprimable de l'édition ; export tableur des inscrits
- Intentions déposées par des visiteurs non inscrits ; modération préalable
- Interface multilingue

---

## 8. Points ouverts

1. **Prochaine édition** : date, heure de début, date d'ouverture des inscriptions (recommandation : 4 à 6 semaines avant).
2. **Durée de conservation des données** : la Communion a confirmé qu'il n'y aurait aucune purge. La CNIL demande une durée définie et proportionnée ; l'écart est à réexaminer. L'application sait déjà anonymiser un priant, donc une purge automatique (ex. 3 ans après la dernière participation) pourra être ajoutée sans refonte.
3. **Textes juridiques** : libellé du consentement, page de confidentialité et mentions légales, responsable du traitement.
4. **Réglages des notifications** : heure du rappel de la veille (18h proposée), délai de l'alarme .ics (10 minutes proposées).
5. **Volume attendu de priants**, pour dimensionner l'envoi des e-mails.
6. **Hébergement, nom de domaine, adresse d'expédition des e-mails, budget.**
7. **Premiers organisateurs** : liste des adresses e-mail.
8. **Contenu de la première édition** : présentation, intention générale, constitution de la bibliothèque des 48 textes.
9. **Orthographe exacte du nom de la Communion** (traits d'union : « Notre-Dame du Mont-Carmel » ?).

---

## Annexe A — Journal des décisions

| # | Sujet | Décision |
|---|---|---|
| 1 | Lieu de prière | Chez soi, à distance |
| 2 | Fuseau horaire | Heure de Paris, plus l'heure locale si le navigateur est dans un autre fuseau |
| 3 | Éditions | Plusieurs éditions, un seul groupe ; une seule édition publique à la fois |
| 4 | Horaires | Heure de début réglable, 48 créneaux de 30 min ; inscriptions ouvertes 4 à 6 semaines avant |
| 5 | Public | Mixte, dont des personnes âgées : conçu pour le téléphone, très simple |
| 6 | Priants par créneau | Pas de plafond |
| 7 | Accès | Lien ouvert : tout le monde s'inscrit ; les noms ne sont visibles que des inscrits |
| 8 | Identification | E-mail et lien personnel, sans mot de passe |
| 9 | Affichage des noms | Nom complet |
| 10 | Inscription pour autrui | Inscription de groupe (nom de groupe et nombre de personnes) |
| 11 | Comptage d'un groupe | Compte pour sa taille ; la notion d'objectif est supprimée |
| 12 | Créneaux vides | Code couleur, bandeau, bouton « Prendre un créneau vide », suggestion après inscription |
| 13 | Relances | Relance par e-mail déclenchée par un organisateur |
| 14 | Rappels | Confirmation (avec .ics) et rappel la veille |
| 15 | Désinscription | Libre, sans alerte aux organisateurs |
| 16 | Texte | Un texte par créneau |
| 17 | Accès au texte | Dès l'inscription ; titre et source publics |
| 18 | Fiche texte | Titre, source, corps mis en forme, pistes de méditation, version imprimable |
| 19 | Saisie des textes | Bibliothèque réutilisable et attribution aux créneaux |
| 20 | Pendant les 24h | Créneau en cours mis en avant, pointage « J'ai prié », mot de relais |
| 21 | Mot de relais | Visible des inscrits, publié directement, suppression possible par les organisateurs |
| 22 | Pointage | Visible de tous les inscrits |
| 23–24 | Intentions | Déposées par les inscrits seulement ; publiées directement |
| 25 | Organisateurs | Quelques comptes nommés, mêmes droits, connexion par lien personnel |
| 26 | Outils organisateurs | Gestion des inscriptions, message libre aux inscrits |
| 27 | Après l'édition | Toutes les données sont conservées |
| 28 | Édition suivante | Invitation des anciens priants, reconnus à leur retour |
| 29 | Identité | Esprit carmélitain ; « Communion de prière Notre-Dame du Mont Carmel » |
| 30 | Plusieurs créneaux | Aucune limite |
| 31 | Statut « Programmée » | Un brouillon ne s'ouvre jamais seul : l'organisateur le programme, puis il s'ouvre à la date prévue |
| 32 | Durée des créneaux | 30 minutes, fixe |
| 33 | Lien d'invitation | Vaut lien personnel (ouvre une session) |
| 34 | Inscription saisie par un organisateur | Pas de recueil de consentement (écart RGPD assumé) |
| 35 | Durée de conservation | Aucune purge (écart RGPD assumé) |

## Annexe B — Modèle de données conceptuel

```
Organisateur (email)

Édition (titre, présentation, début, durée_créneau, ouverture_inscriptions, statut)
  └─ Créneau (édition, rang, début, fin)  ──►  Texte (0..1)

Texte (titre, source, corps, pistes_méditation)          — bibliothèque commune

Priant (email, consentement_le, désabonné_invitations)
  └─ Inscription (créneau, priant?, nom_affiché, nb_personnes,
                  pointé_le?, mot_de_relais?)            — priant facultatif si créée par un organisateur

Intention (édition, priant, texte, anonyme, créée_le)

Envoi (édition, type, objet, corps, envoyé_le, nb_destinataires, organisateur)
```
