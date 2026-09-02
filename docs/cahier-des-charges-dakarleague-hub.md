# CAHIER DES CHARGES — DAKARLEAGUE HUB

**Projet de fin de stage — Conception et réalisation d'une plateforme web de gestion de championnats et tournois de football amateur**

| Rubrique | Information |
|---|---|
| Projet | Projet de fin de stage — Licence en Ingénierie Logicielle |
| Étudiant | Boubs |
| Entreprise d'accueil | SunuCode |
| Encadrant entreprise | [Nom et prénom] |
| Encadrant académique | [Nom et prénom] |
| Version | 1.1 — Août 2026 |
| Fait à | Dakar, le [date] |

---

## Sommaire

1. [Présentation générale du projet](#1-présentation-générale-du-projet)
2. [Analyse des besoins](#2-analyse-des-besoins)
3. [Spécifications techniques](#3-spécifications-techniques)
4. [Livrables attendus](#4-livrables-attendus)
5. [Gestion des risques](#5-gestion-des-risques)
6. [Critères de recette](#6-critères-de-recette)
7. [Évolutions possibles](#7-évolutions-possibles)
8. [Annexes](#8-annexes)

---

## 1. Présentation générale du projet

### 1.1 Contexte

Le football amateur occupe une place importante dans la vie sportive et sociale au Sénégal, notamment à Dakar. Pourtant, de nombreux championnats et tournois sont encore organisés avec des cahiers, des fichiers Excel et des groupes WhatsApp.

Cette gestion dispersée entraîne des erreurs de saisie, des retards de publication, des litiges sur les classements et une faible visibilité des compétitions.

- Erreurs ou incohérences dans les résultats et les classements.
- Faible traçabilité des reports, pénalités et décisions sportives.
- Charge administrative importante pour les organisateurs.
- Accès difficile aux informations pour les équipes, joueurs, supporters et sponsors.
- Manque de visibilité numérique des compétitions locales.

### 1.2 Objectif général

Concevoir et développer **DakarLeague Hub**, une application web responsive permettant d'organiser, d'administrer et de rendre visibles des championnats et tournois de football amateur dans le cadre d'un projet de fin de stage.

### 1.3 Objectifs spécifiques

- Gérer les compétitions, équipes, joueurs, matchs et résultats.
- Automatiser la génération du calendrier et le calcul du classement.
- Produire des statistiques individuelles et collectives.
- Mettre à disposition des pages publiques pour les supporters.
- Faciliter le travail des organisateurs grâce à un tableau de bord sécurisé.
- Préparer une architecture évolutive pour une API, une application mobile ou un paiement en ligne.

### 1.4 Périmètre fonctionnel

Le projet se concentre sur les fonctionnalités essentielles nécessaires à une démonstration complète. Les fonctions secondaires et les évolutions futures ne doivent pas compromettre le fonctionnement du cœur de l'application.

| Priorité | Fonctionnalités |
|---|---|
| **Essentielles — MVP** | Authentification et rôles ; compétitions ; équipes et joueurs ; calendrier ; matchs ; résultats ; buts et cartons ; classement automatique ; pages publiques ; dashboard ; export PDF ; statistiques essentielles ; journalisation. |
| **Secondaires — selon faisabilité** | Compositions, titulaires et remplaçants ; inscriptions d'équipes ; statistiques plus détaillées ; critères avancés de fair-play et confrontation directe. |
| **Hors périmètre** | Application mobile native ; paiement réel Wave/Orange Money ; streaming ; live score temps réel ; prédictions IA ; analyse vidéo ; intégration de données externes. |

---

## 2. Analyse des besoins

### 2.1 Acteurs

| Acteur | Responsabilités |
|---|---|
| **Super-administrateur** | Gère les utilisateurs, rôles, compétitions, paramètres et modération. |
| **Organisateur** | Crée et configure une compétition, valide les équipes, gère le calendrier, les matchs, les résultats et les documents. |
| **Responsable d'équipe** | Représente une équipe et consulte ses données ; peut gérer les joueurs si cette permission est activée. |
| **Joueur** | Consulte son profil et ses statistiques selon les droits attribués. |
| **Visiteur** | Consulte librement les compétitions, résultats, calendrier, classement et statistiques publiées. |

### 2.2 Cas d'utilisation

| Réf. | Cas d'utilisation | Acteur |
|---|---|---|
| UC01 | Créer, modifier et archiver une compétition | Super-admin / Organisateur |
| UC02 | Configurer le format, les points et les règles | Organisateur |
| UC03 | Créer, modifier ou retirer une équipe | Organisateur |
| UC04 | Enregistrer et gérer les joueurs | Organisateur / Responsable |
| UC05 | Générer, modifier et publier le calendrier | Organisateur |
| UC06 | Créer, reprogrammer ou annuler un match | Organisateur |
| UC07 | Saisir résultats, buts, cartons et compositions | Organisateur |
| UC08 | Calculer et publier les classements | Système / Organisateur |
| UC09 | Consulter les statistiques | Tous selon droits |
| UC10 | Exporter calendrier, classement ou rapport PDF | Organisateur |
| UC11 | Gérer les utilisateurs et rôles | Super-admin |
| UC12 | Consulter les pages publiques | Visiteur |

### 2.3 Matrice des droits

| Fonctionnalité | Super-admin | Organisateur | Responsable | Visiteur |
|---|---|---|---|---|
| Consulter les pages publiques | Oui | Oui | Oui | Oui |
| Créer une compétition | Oui | Oui | Non | Non |
| Gérer équipes et joueurs | Oui | Oui | Selon permission | Non |
| Gérer calendrier et matchs | Oui | Oui | Non | Non |
| Saisir ou modifier un résultat | Oui | Oui, avec traçabilité | Non | Non |
| Consulter le dashboard | Oui | Oui, pour sa compétition | Non | Non |
| Gérer utilisateurs et rôles | Oui | Non | Non | Non |

### 2.4 Exigences fonctionnelles

**A. Compétitions**
- Créer une compétition avec nom, saison, catégorie, description, logo et dates.
- Gérer les statuts : brouillon, inscriptions ouvertes, en cours, terminée, archivée.
- Choisir un format prioritaire : championnat aller simple ou aller-retour.
- Prévoir les formats poules et élimination directe comme extensions.
- Configurer nombre d'équipes, points victoire/nul/défaite et départage.

**B. Équipes et joueurs**
- Gérer nom, logo, couleurs, ville, terrain, responsable et contact d'une équipe.
- Gérer identité, date de naissance, poste, numéro, photo et licence facultative d'un joueur.
- Garantir l'unicité du nom d'équipe dans une compétition.
- Garantir qu'un joueur ne soit lié qu'à une équipe pour une même compétition et saison.

**C. Calendrier et matchs**
- Générer les rencontres selon le format choisi.
- Modifier date, heure, lieu ou équipe avant validation.
- Gérer les états programmé, en cours, terminé, reporté et annulé.
- Empêcher deux matchs au même créneau pour une même équipe.
- Conserver le motif et l'historique des reports et annulations.

**D. Résultats et événements**
- Saisir le score final, buts, penalties, buts contre son camp et cartons.
- Associer un événement à une minute, une équipe et, si disponible, un joueur.
- Refuser la validation lorsque les buts saisis ne correspondent pas au score final.
- Réserver les compositions détaillées à une fonctionnalité secondaire.

**E. Classements et statistiques**
- Recalculer automatiquement le classement après validation d'un match terminé.
- Afficher rang, matchs joués, victoires, nuls, défaites, buts pour/contre, différence et points.
- Afficher top buteurs, cartons, forme récente, meilleure attaque et défense.
- Appliquer les critères de départage configurés par compétition.

**F. Pages publiques**
- Afficher compétition, classement, derniers résultats et prochaines rencontres.
- Afficher les pages équipe et joueur avec les données publiées.
- Permettre le partage des liens sur les réseaux sociaux et WhatsApp.
- Garantir la consultation sans connexion et sur smartphone.

**G. Dashboard et documents**
- Afficher équipes, matchs programmés, matchs terminés, résultats à valider et échéances.
- Exporter au minimum le calendrier et le classement au format PDF.
- Afficher total de buts, moyenne de buts par match et nombre de cartons.

**H. Authentification et traçabilité**
- Prévoir inscription, connexion, déconnexion, récupération et modification du mot de passe.
- Gérer nom, prénom, email et téléphone facultatif.
- Appliquer les restrictions par rôle et par compétition.
- Journaliser création, modification de score, report, annulation et validation.

### 2.5 Règles de gestion

| Réf. | Règle |
|---|---|
| RG01 | Une compétition possède un nom, une saison, un format et un statut obligatoires. |
| RG02 | Une équipe est rattachée à une compétition et son nom est unique dans celle-ci. |
| RG03 | Un joueur ne peut appartenir qu'à une équipe dans une même compétition et saison. |
| RG04 | Un match oppose deux équipes distinctes inscrites dans la même compétition. |
| RG05 | Un match terminé possède un score valide et des événements cohérents. |
| RG06 | Points par défaut : victoire 3, nul 1, défaite 0 ; ils sont configurables. |
| RG07 | Seuls les matchs terminés et validés sont pris en compte au classement. |
| RG08 | Un match annulé ou reporté ne produit aucun point sans résultat valide. |
| RG09 | Départage par défaut : points, différence de buts, buts marqués, fair-play, confrontation directe. |
| RG10 | Toute modification après validation est réservée à l'organisateur et enregistrée dans le journal. |
| RG11 | Les pages publiques ne montrent ni email, ni téléphone, ni mot de passe, ni document de licence. |
| RG12 | Un utilisateur accède uniquement aux fonctions autorisées par son rôle et son périmètre. |

### 2.6 Exigences non fonctionnelles

- **Performance** : temps de chargement cible inférieur à 3 secondes pour les pages principales ; pagination, indexation et chargement optimisé des relations Laravel.
- **Sécurité** : mots de passe hachés, protection CSRF, validation côté serveur, échappement, Eloquent, middleware, policies, HTTPS en production, sauvegardes et validation des fichiers téléversés.
- **Ergonomie** : interface en français, mobile-first, navigation cohérente, messages explicites, confirmations avant suppression, contrastes lisibles et navigation clavier de base.
- **Maintenabilité** : architecture MVC, conventions de nommage, migrations, seeders, Git/GitHub, documentation et tests des droits, du calendrier, des scores et du classement.

---

## 3. Spécifications techniques

| Couche | Choix | Justification |
|---|---|---|
| Backend | PHP 8+ et Laravel 10/11 | MVC, sécurité, Eloquent, migrations et écosystème. |
| Frontend | Blade + Tailwind CSS ou Bootstrap 5 | Responsive, productivité et cohérence visuelle. |
| JavaScript | JavaScript natif / Alpine.js | Interactions simples sans complexité excessive. |
| Base de données | MySQL 8+ | Modèle relationnel compatible Laravel/XAMPP. |
| Graphiques | Chart.js | Visualisation des statistiques. |
| PDF | barryvdh/laravel-dompdf | Export des calendriers et classements. |
| Outils | VS Code, XAMPP, Composer, Node.js | Environnement de développement local. |
| Versionnage | Git et GitHub | Traçabilité, collaboration et sauvegarde. |
| Déploiement | Hébergement PHP/MySQL ou VPS | Démonstration en ligne. |

### 3.1 Architecture cible

L'application adoptera une architecture MVC. Les contrôleurs reçoivent les requêtes HTTP, les modèles Eloquent accèdent à MySQL, les services regroupent les règles complexes et les vues Blade affichent l'interface. Les services recommandés sont `CalendarService` pour les rencontres et `StandingsService` pour le classement.

### 3.2 Entités et relations principales

| Entité | Rôle et relations principales |
|---|---|
| `users` / `roles` | Utilisateurs et rôles ; un utilisateur peut être organisateur ou responsable selon ses permissions. |
| `competitions` | Compétition, saison, format, statut, paramètres et critères de départage. |
| `competition_organizers` | Relation entre compétitions et organisateurs autorisés. |
| `teams` | Équipes rattachées à une compétition ; nom unique dans celle-ci. |
| `players` | Joueurs rattachés à une équipe et à une compétition. |
| `matches` | Rencontre entre deux équipes, date, lieu, statut et score. |
| `match_events` | Buts et cartons liés à un match, une équipe et éventuellement un joueur. |
| `lineups` | Compositions détaillées, prévue comme fonctionnalité secondaire. |
| `standings` | Classement calculé à partir des matchs validés ; matérialisation à décider lors de la conception. |
| `documents` / `activity_logs` | Documents exportés ou téléversés et journal des actions sensibles. |

### 3.3 Décisions de conception à valider

- Le format prioritaire du MVP est le championnat aller simple ou aller-retour.
- Le classement est calculé à partir des matchs terminés et validés ; une table matérialisée sera ajoutée seulement si nécessaire.
- Les permissions du responsable d'équipe sont activables par compétition.
- Les compositions détaillées, poules et élimination directe sont secondaires et ne doivent pas bloquer le MVP.

### 3.4 Identité visuelle et charte graphique

L'identité visuelle de la plateforme reprend le logo officiel **DakarLeague Hub** : un écusson sombre au tracé vert néon, avec un pictogramme de joueur blanc frappant un ballon. Cette charte (fond sombre, accent vert lime, contenu blanc) doit être appliquée à l'ensemble de l'interface — dashboard, pages publiques, exports PDF — pour garantir une cohérence de marque.

**Palette de couleurs**

| Rôle | Usage | Code hexadécimal | Variable Tailwind proposée |
|---|---|---|---|
| Fond principal | Arrière-plan général de l'application (dashboard, pages publiques) | `#0D0D0D` | `background` |
| Fond secondaire / cartes | Cartes, panneaux, tableaux, modales | `#1A1A1A` | `surface` |
| Accent principal (vert lime) | Boutons d'action, liens actifs, icônes, bordures de l'écusson, mise en avant (score, classement, victoires) | `#B6FF3B` | `primary` |
| Accent secondaire (vert foncé) | États hover/actifs, dégradés, halo lumineux autour de l'accent | `#7ACC00` | `primary-dark` |
| Texte principal | Titres et texte sur fond sombre | `#FFFFFF` | `text` |
| Texte secondaire | Sous-titres, légendes, texte atténué | `#B3B3B3` | `text-muted` |
| Bordures / séparateurs | Lignes de séparation, contours discrets | `#2A2A2A` | `border` |
| État succès | Match validé, victoire, confirmation | `#B6FF3B` | `success` |
| État alerte / erreur | Match annulé, erreur de saisie, incohérence de score | `#FF4D4D` | `danger` |

**Typographie recommandée**

- Titres et logo : police sans-serif grasse et condensée (ex. Poppins Bold, Montserrat ExtraBold), en capitales pour les titres de marque, à l'image du texte « DAKARLEAGUE » du logo.
- Corps de texte : police sans-serif lisible (ex. Inter, Roboto) pour les tableaux de classement, formulaires et pages publiques.

**Application dans l'interface**

- Le fond sombre (`background`) et les cartes (`surface`) structurent le dashboard organisateur et les pages publiques, avec le vert lime (`primary`) réservé aux actions principales, aux indicateurs clés (buts, victoires, top buteurs) et aux éléments de marque (logo, liens actifs, barres de progression).
- Le blanc (`text`) reste la couleur dominante du texte sur fond sombre, conformément au pictogramme du joueur dans le logo.
- Les exports PDF (calendrier, classement) reprennent le logo et les couleurs de la charte en en-tête, sur un fond clair pour l'impression, avec le vert lime en accent (titres de tableau, lignes de classement en tête).
- La palette est centralisée dans la configuration Tailwind (`tailwind.config.js`) sous forme de variables réutilisables, afin de faciliter toute évolution de la charte graphique.

---

## 4. Livrables attendus

- Code source versionné sur GitHub avec migrations, seeders et configuration d'exemple.
- Application web fonctionnelle installée en local et/ou déployée en ligne.
- Base de données avec compétition, équipes, joueurs, matchs et données fictives réalistes.
- Documentation technique : prérequis, installation, configuration, lancement et déploiement.
- Manuel utilisateur destiné aux organisateurs et visiteurs.
- Cahier de tests avec résultats des scénarios principaux.
- Mémoire de fin de stage et support de présentation / démonstration.

---

## 5. Gestion des risques

| Risque | Impact | Prévention / action |
|---|---|---|
| Dérive du périmètre | Élevé | Prioriser les fonctionnalités essentielles ; déplacer les fonctions secondaires vers les évolutions. |
| Bug de classement ou calendrier | Élevé | Utiliser des services dédiés et tester 4, 6 et 8 équipes avec plusieurs égalités. |
| Données incohérentes | Élevé | Validation serveur, contraintes relationnelles, transactions et confirmation avant validation. |
| Permissions incorrectes | Élevé | Matrice des droits, middleware, policies et tests avec chaque rôle. |
| Hébergement | Moyen | Tester le déploiement, vérifier les variables d'environnement et prévoir des sauvegardes. |
| Données de démonstration insuffisantes | Moyen | Créer des seeders réalistes couvrant résultats, reports et classements. |
| Documentation incomplète | Moyen | Documenter les décisions et les fonctionnalités au fur et à mesure. |

---

## 6. Critères de recette

La recette vérifie les fonctionnalités essentielles, la cohérence des données, les droits d'accès et l'utilisation sur différents écrans.

| ID | Scénario | Résultat attendu |
|---|---|---|
| CR01 | Création compétition | Les champs obligatoires sont validés et la compétition est enregistrée. |
| CR02 | Équipes et joueurs | Les données sont créées, modifiables et correctement reliées. |
| CR03 | Génération calendrier | Aucune équipe ne joue contre elle-même ; rencontres conformes au format. |
| CR04 | Conflit de créneau | Le système refuse deux matchs simultanés pour une même équipe. |
| CR05 | Saisie résultat | Scores, buts et cartons cohérents ; incohérences refusées. |
| CR06 | Classement | Après validation, points, buts, différence et rangs sont recalculés. |
| CR07 | Modification post-validation | Seul l'organisateur habilité peut modifier ; l'action est tracée. |
| CR08 | Droits d'accès | Le visiteur ne peut ni créer ni modifier de données. |
| CR09 | Pages publiques | Classement, matchs et équipes sont visibles sans connexion. |
| CR10 | Export PDF | Un calendrier ou classement PDF est généré et téléchargeable. |
| CR11 | Responsive | Les parcours essentiels fonctionnent sur mobile, tablette et ordinateur. |
| CR12 | Qualité finale | Aucun bug bloquant dans le scénario de démonstration. |

### 6.1 Scénario de démonstration recommandé

Créer une compétition, ajouter plusieurs équipes et joueurs, générer le calendrier, saisir un match terminé avec buts et cartons, vérifier le classement, consulter la page publique, tester un accès non autorisé et exporter le classement en PDF.

---

## 7. Évolutions possibles

- Application mobile Flutter ou React Native consommant une API REST.
- Inscription en ligne des équipes et paiement par Wave ou Orange Money.
- Notifications WhatsApp/SMS pour résultats, convocations et changements de calendrier.
- Live score et validation des résultats par arbitre.
- Gestion avancée des licences, sanctions, arbitres et terrains.
- Multilingue : français, anglais et wolof.
- Analyse de performance et recommandations par intelligence artificielle.
- Connexion à des APIs de données sportives sous réserve de licence et de budget.

---

## 8. Annexes

### 8.1 Glossaire

| Terme | Définition |
|---|---|
| MVP | Version minimale viable contenant les fonctions indispensables à la démonstration. |
| CRUD | Création, lecture, modification et suppression de données. |
| MVC | Architecture séparant modèles, vues et contrôleurs. |
| ORM | Outil permettant de manipuler les données relationnelles avec des objets ; Eloquent dans Laravel. |
| UC | Cas d'utilisation : interaction attendue entre un acteur et le système. |
| RG | Règle de gestion : contrainte métier à respecter. |
| Responsive | Interface adaptée à la taille de l'écran. |
| Policy | Mécanisme Laravel permettant d'autoriser une action sur une ressource. |

### 8.2 Validation

Ce cahier des charges constitue la référence fonctionnelle initiale du projet de fin de stage. Toute nouvelle demande sera évaluée selon son utilité, sa faisabilité et son impact sur le périmètre essentiel.

| Partie | Nom et signature | Date |
|---|---|---|
| Étudiant | Boubs — Signature : ____________________ | ________________ |
| Encadrant entreprise | [Nom et prénom] — Signature : ____________________ | ________________ |
| Encadrant académique | [Nom et prénom] — Signature : ____________________ | ________________ |
