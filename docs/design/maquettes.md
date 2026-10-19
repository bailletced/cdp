# Maquettes mobiles basse fidélité (360 px)

> **F01-T03 / F01-T04** — Brouillons à relire par un organisateur. Vues à 360 px de large
> (téléphone de Marie), thème carmélitain ([theme.md](theme.md)) : corps 18 px, cibles tactiles
> ≥ 44 × 44 px, contour de focus primaire 3 px, information jamais portée par la seule couleur.
>
> Légende : `▢` champ ou bouton (hauteur ≥ 44 px) · `─` séparateur `line` · `●` action
> principale (bouton rempli `primary`) · `○` action secondaire (contour `primary`).

## 1. Accueil — bandeau + grille (F01-T03)

```
┌────────────────────────────┐
│ Communion de prière        │  ← en-tête (brand, lien vers /), nav : « Recevoir mon lien »
│ Notre-Dame du Mont-Carmel  │    (lien souligné, cible ≥ 44 px)
├────────────────────────────┤
│ ┌────────────────────────┐ │
│ │ 24 HEURES DE PRIÈRE    │ │  ← bandeau `surface` crème
│ │                        │ │
│ │ Un temps de prière     │ │    corps 18 px
│ │ partagé, 24 h sur 24.  │ │
│ │                        │ │
│ │   ● Voir les créneaux  │ │    bouton primaire, ≥ 44 px
│ └────────────────────────┘ │
├────────────────────────────┤
│ Créneaux                   │  ← h2
│ ┌───────────┐ ┌───────────┐│
│ │20:00      │ │20:30      ││  ← grille 2 colonnes, cartes `surface`
│ │● Libre    │ │○ Complet  ││    statut : pastille + libellé (jamais couleur seule)
│ └───────────┘ └───────────┘│
│ ┌───────────┐ ┌───────────┐│
│ │21:00      │ │21:30      ││
│ │● Libre    │ │● Libre    ││
│ └───────────┘ └───────────┘│
│ … 48 créneaux au total …   │
├────────────────────────────┤
│ Confidentialité · Recevoir │  ← pied de page (liens ≥ 44 px)
│ mon lien                   │
└────────────────────────────┘
```

- Le lien d'évitement « Aller au contenu » saute directement la grille (F01-T07).
- Chaque carte-créneau est un lien (cible ≥ 44 px de haut) ; le statut « Libre » est
  sélectionnable même pour un créneau complet (désinscription, F14).

## 2. Formulaire d'inscription (F01-T03)

```
┌────────────────────────────┐
│ … en-tête …                │
├────────────────────────────┤
│ Inscription                │  ← h1 (reçoit le focus à l'arrivée, F01-T08)
│ Créneau du samedi 28/11,   │
│ 20:00 – 20:30              │
│                            │
│ Nom                        │  ← libellé du champ (FormField, F01-T09)
│ ▢ Dupont                   │    champ ≥ 44 px, contour `line`, focus primaire 3 px
│                            │
│ Adresse e-mail             │
│ ▢ marie@exemple.fr         │
│ Aide : un lien de connexion│   ← aide liée au champ (aria-describedby)
│ vous sera envoyé.          │
│                            │
│ ▢ J'accepte la politique   │   ← case ≥ 44 px (cible = case + libellé)
│   de confidentialité       │
│                            │
│ ● S'inscrire               │   ← bouton primaire ≥ 44 px, désactivé tant que
│                            │     le formulaire est invalide
│ ○ Annuler                  │   ← retour à l'accueil
├────────────────────────────┤
│ … pied de page …           │
└────────────────────────────┘
```

- À la soumission, une erreur 422 place le message **sous le champ concerné**, relié par
  `aria-describedby`, annoncé par le lecteur d'écran, et le focus revient sur le premier
  champ en erreur (F01-T09).

## 3. Confirmation d'inscription (F01-T03)

```
┌────────────────────────────┐
│ … en-tête …                │
├────────────────────────────┤
│ C'est noté !               │  ← h1
│                            │
│ ┌────────────────────────┐ │
│ │ ✓ Inscription          │ │  ← carte `surface` : icône + libellé « enregistrée »
│ │   enregistrée          │ │    (succès = icône + texte, pas seulement vert)
│ │                        │ │
│ │ Samedi 28/11, 20:00    │ │
│ │ Un e-mail avec votre   │ │
│ │ texte et votre lien    │ │
│ │ arrive.                │ │
│ └────────────────────────┘ │
│                            │
│ ○ Voir mon espace          │  ← si session ouverte
│ ● Prendre un autre créneau│    suggestion de créneau libre voisin (F13)
├────────────────────────────┤
│ … pied de page …           │
└────────────────────────────┘
```

- Une notification accessible confirme l'action (région `aria-live`, fermable au clavier,
  F01-T10) en complément de cette page.

## 4. Mon espace (F01-T04)

```
┌────────────────────────────┐
│ … en-tête (nav : « Mon     │
│ espace », « Me déconnecter »)│
├────────────────────────────┤
│ Mon espace                 │  ← h1
│                            │
│ Bonjour,                   │  ← identité : nom modifiable (FormField en ligne)
│ ▢ Dupont            ✎      │
│                            │
│ ┌────────────────────────┐ │
│ │ PENDANT MON CRÉNEAU    │ │  ← bloc `surface`, visible le jour J (F21)
│ │ Samedi 20:00 – 20:30   │ │
│ │                        │ │
│ │ ● Lire mon texte       │ │    lien vers le texte (F12)
│ │ ● J'ai prié            │ │    bouton ≥ 44 px (pointage, F19)
│ └────────────────────────┘ │
│                            │
│ Mes créneaux               │  ← h2
│ Sam 28/11 20:00            │  ← liste ; chaque ligne : heure + action
│               ○ Me désinscrire   (ConfirmDialog, F01-T09, cible ≥ 44 px)
│ Dim 29/11 06:30            │
│               ○ Me désinscrire
│                            │
│ ○ Recevoir mon lien        │  ← sur un nouvel appareil (F02)
│ ○ Me déconnecter           │  ← contour danger, ≥ 44 px
├────────────────────────────┤
│ … pied de page …           │
└────────────────────────────┘
```

- « Me désinscrire » ouvre une boîte de confirmation (focus piégé, Échap ferme, F01-T09) :
  aucune suppression sans confirmation.

## 5. Lecture d'un texte (F01-T04)

```
┌────────────────────────────┐
│ … en-tête …                │
├────────────────────────────┤
│ [titre du texte]           │  ← h1 (32 px)
│ Prière du matin            │  ← méta `muted` : auteur, durée (18 px)
│                            │
│ ─────────────────────────  │
│                            │
│ Ô mon Dieu, …              │  ← corps 18 px, interlignage 1,6,
│                            │    max-width 70ch, sur fond blanc
│ …                          │
│                            │
│ ● J'ai prié                │  ← bouton primaire ≥ 44 px (F19)
│ ○ Version imprimable       │  ← @media print (F28)
├────────────────────────────┤
│ … pied de page …           │
└────────────────────────────┘
```

## 6. Bloc « En ce moment » (F01-T04)

Carte `surface` crème, en haut de l'accueil pendant les 24 heures (F17) :

```
┌────────────────────────────┐
│ ┌────────────────────────┐ │
│ │ EN CE MOMENT           │ │  ← h2 + pastille « en cours » (texte, pas seule couleur)
│ │                        │ │
│ │ Samedi 28/11, 20:30    │ │
│ │ « Prière du soir »     │ │    texte attribué au créneau
│ │ par Marie D.           │ │    (ou « Anonyme » — Q7)
│ │                        │ │
│ │ Prochain créneau dans  │ │    compte à rebours (aria-live poli)
│ │ 12 min                 │ │
│ └────────────────────────┘ │
│ ○ Voir le planning complet │
└────────────────────────────┘
```

- Mise à jour temps réel (Mercure, F18) avec repli sur rafraîchissement périodique ; le
  changement de créneau est annoncé par une région `aria-live` (F01-T10).
