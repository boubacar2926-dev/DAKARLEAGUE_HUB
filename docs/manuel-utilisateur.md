# Manuel utilisateur — DakarLeague Hub

Guide pratique à destination des **visiteurs**, des **organisateurs** et des **responsables d'équipe**. Pour l'installation et la configuration technique, voir le [README.md](../README.md).

## Sommaire

1. [Pour les visiteurs](#1-pour-les-visiteurs)
2. [Se connecter](#2-se-connecter)
3. [Pour les organisateurs](#3-pour-les-organisateurs)
4. [Pour les responsables d'équipe](#4-pour-les-responsables-déquipe)
5. [Comptes de démonstration](#5-comptes-de-démonstration)

---

## 1. Pour les visiteurs

Aucune connexion n'est nécessaire pour consulter les compétitions publiées.

### 1.1 Trouver une compétition

Depuis la page d'accueil ou le lien **Compétitions** du menu, la liste des compétitions publiées s'affiche (nom, saison, catégorie, nombre d'équipes validées). Cliquer sur une compétition ouvre sa page dédiée.

### 1.2 Naviguer dans une compétition

Chaque page de compétition propose un sous-menu à onglets :

| Onglet | Contenu |
|---|---|
| **Aperçu** | Description, prochaines rencontres, derniers résultats |
| **Classement** | Tableau complet (rang, joués, V/N/D, buts pour/contre, différence, points, forme sur les 5 derniers matchs) |
| **Statistiques** | Meilleure attaque, meilleure défense, top buteurs, discipline (cartons) |
| **Calendrier** | Matchs regroupés par journée, avec date/lieu et statut (programmé, reporté, annulé, terminé) |
| **Résultats** | Scores des matchs terminés, avec le détail des buts/cartons et, si saisie, la composition (bouton *Voir la composition*) |

Depuis le classement, cliquer sur le nom d'une équipe ouvre sa **fiche équipe** (effectif, numéros, postes). Depuis un effectif ou le classement des buteurs, cliquer sur un joueur ouvre sa **fiche joueur** (statistiques : buts, cartons).

> Aucune donnée personnelle sensible (email, téléphone, numéro de licence) n'est jamais affichée sur les pages publiques.

## 2. Se connecter

Le bouton **Connexion** (en haut à droite) mène au formulaire de connexion. Un nouvel utilisateur peut créer un compte via **Inscription** (nom, email, mot de passe). Par défaut, un compte créé ainsi a le rôle **joueur** ; il devient automatiquement **responsable** dès qu'il inscrit une équipe en ligne (voir §4).

Après connexion, le bouton **Tableau de bord** du menu conduit à l'espace correspondant au rôle du compte.

## 3. Pour les organisateurs

Accès réservé aux comptes de rôle **organisateur** ou **super-administrateur**.

### 3.1 Tableau de bord

Après connexion, le tableau de bord organisateur liste les compétitions gérées (créées, ou pour lesquelles le compte a été désigné organisateur), les prochaines rencontres et les résultats en attente de saisie (matchs dont la date est passée mais non validés).

### 3.2 Créer une compétition

Depuis **+ Nouvelle compétition** : renseigner nom, saison, catégorie, dates, format (championnat aller simple / aller-retour / poules / élimination directe), statut, points victoire/nul/défaite, et — si l'inscription en ligne des équipes doit être ouverte — cocher *« Autoriser les responsables d'équipe à gérer leurs joueurs »* si souhaité.

Les statuts disponibles suivent le cycle de vie d'une compétition : **Brouillon** → **Inscriptions ouvertes** → **En cours** → **Terminée** → **Archivée**. Le statut *Inscriptions ouvertes* est celui qui affiche le bouton d'inscription en ligne sur la page publique (voir §4.1).

### 3.3 Ajouter et valider des équipes

Depuis la fiche d'une compétition → **Équipes & joueurs** :

- **+ Ajouter une équipe** : création directe par l'organisateur (validée immédiatement).
- Les équipes soumises en ligne par un responsable apparaissent avec le statut **En attente de validation** ; les boutons **Valider** / **Refuser** permettent de trancher. Tant qu'une équipe n'est pas validée, elle n'apparaît ni dans le classement, ni dans le calendrier généré, ni dans les compteurs publics.

### 3.4 Ajouter des joueurs

Depuis la liste des équipes → **Joueurs** (sur une équipe), puis **+ Ajouter un joueur** : prénom, nom, date de naissance, poste, numéro de maillot (unique dans l'équipe), photo et numéro de licence facultatifs.

### 3.5 Générer et gérer le calendrier

Depuis **Calendrier & résultats** :

- **Générer le calendrier** : crée automatiquement toutes les rencontres (aller simple ou aller-retour selon le format choisi) entre les équipes validées. Disponible une seule fois tant qu'aucun match n'existe déjà.
- **+ Ajouter un match** : création manuelle d'une rencontre (utile pour les formats poules/élimination directe ou un ajustement ponctuel). Le système refuse deux matchs à la même date/heure pour une même équipe.
- **Modifier** sur un match : changer la date, le lieu, ou son statut (**Reporté** / **Annulé**, avec motif obligatoire).
- **Composition** : saisir les titulaires et remplaçants de chaque équipe pour ce match (11 titulaires maximum par équipe).

### 3.6 Saisir un résultat

Depuis **Saisir résultat** (ou **Modifier résultat** si déjà saisi) sur un match : indiquer le score final, puis ajouter chaque but et carton (équipe, joueur, minute, type : but, but sur penalty, but contre son camp, carton jaune, carton rouge). Le nombre de buts saisis pour chaque équipe doit correspondre exactement au score déclaré — sinon le formulaire refuse l'enregistrement et indique l'écart.

Une fois validé, le match passe au statut **Terminé** et le classement de la compétition se met à jour immédiatement.

### 3.7 Exporter en PDF

Depuis la fiche compétition ou la page calendrier : **Exporter le calendrier (PDF)** / **Exporter le classement (PDF)** téléchargent un document prêt à imprimer, aux couleurs DakarLeague Hub.

### 3.8 Consulter le journal d'activité

Le lien **Journal** du menu liste toutes les actions sensibles (créations, modifications de score, reports, annulations, validations, exports), avec l'utilisateur responsable, la date et un filtre par compétition — utile pour retracer une modification contestée.

## 4. Pour les responsables d'équipe

### 4.1 Inscrire une équipe en ligne

Sur la page publique d'une compétition dont le statut est **Inscriptions ouvertes**, le bouton **Inscrire mon équipe** est visible. Il faut être connecté : le formulaire demande le nom de l'équipe, la ville, le terrain et les coordonnées de contact. La demande est ensuite soumise à l'organisateur (statut *En attente de validation*, voir §3.3). Le compte est automatiquement promu au rôle **responsable**.

### 4.2 Mon espace responsable

Depuis **Tableau de bord**, un responsable retrouve la ou les équipes qu'il gère, le nombre de joueurs par équipe, et les prochaines rencontres. Si l'organisateur a activé la gestion déléguée des joueurs pour la compétition, un bouton **Gérer l'effectif** permet d'ajouter/modifier les joueurs de l'équipe (mêmes formulaires que pour un organisateur, §3.4).

### 4.3 Espace joueur

Un compte de rôle **joueur** lié à un profil joueur (par un organisateur ou un responsable) retrouve depuis son tableau de bord ses statistiques personnelles (buts, cartons) et les prochaines rencontres de son équipe.

## 5. Comptes de démonstration

| Rôle | Email | Mot de passe |
|---|---|---|
| Super-administrateur | `admin@dakarleague.sn` | `password` |
| Organisateur | `organisateur@dakarleague.sn` | `password` |

Ces comptes donnent accès à la compétition de démonstration *Championnat Ligue Amateur de Dakar* (6 équipes, 66 joueurs, calendrier et résultats déjà en partie joués), créée par le jeu de données fourni avec le projet.
