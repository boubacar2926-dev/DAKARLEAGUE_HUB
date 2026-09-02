# DakarLeague Hub

Plateforme web de gestion de championnats et tournois de football amateur — projet de fin de stage (Licence Ingénierie Logicielle, SunuCode).

Le cahier des charges complet est disponible dans [docs/cahier-des-charges-dakarleague-hub.md](docs/cahier-des-charges-dakarleague-hub.md).

Autres documents du projet : [manuel utilisateur](docs/manuel-utilisateur.md) (visiteurs, organisateurs, responsables) et [cahier de tests](docs/cahier-de-tests.md) (résultats de recette et anomalies corrigées).

## Sommaire

1. [Prérequis](#1-prérequis)
2. [Installation](#2-installation)
3. [Configuration](#3-configuration)
4. [Lancement](#4-lancement)
5. [Comptes de démonstration](#5-comptes-de-démonstration)
6. [Architecture du projet](#6-architecture-du-projet)
7. [Structure des dossiers](#7-structure-des-dossiers)
8. [Fonctionnalités et routes principales](#8-fonctionnalités-et-routes-principales)
9. [Tests manuels effectués](#9-tests-manuels-effectués)
10. [Déploiement](#10-déploiement)
11. [Dépannage](#11-dépannage)

---

## 1. Prérequis

| Outil | Version utilisée pour le développement |
|---|---|
| PHP | 8.2+ (extensions : `pdo_mysql`, `mbstring`, `openssl`, `gd` ou `dom` pour DomPDF) |
| Composer | 2.10+ |
| Node.js | 24+ |
| npm | 11+ |
| MySQL | 8+ (fourni par XAMPP en local) |
| Serveur web | Apache (XAMPP) ou tout serveur PHP/MySQL |

Le projet a été développé et testé avec **XAMPP** sous Windows, le dossier du projet étant placé dans `htdocs/stage_defar/fin_stage`.

## 2. Installation

```bash
# 1. Installer les dépendances PHP
composer install

# 2. Installer les dépendances JavaScript
npm install

# 3. Copier le fichier d'environnement et générer la clé d'application
cp .env.example .env
php artisan key:generate

# 4. Créer le lien symbolique vers le stockage public (logos, photos, PDF exportés)
php artisan storage:link
```

## 3. Configuration

### 3.1 Base de données

`.env.example` est déjà pré-configuré pour un environnement XAMPP local (MySQL sans mot de passe, base `dakarleague_hub`, `APP_URL` pointant vers `htdocs/stage_defar/fin_stage/public`). Après la copie en `.env`, il ne reste qu'à créer la base — vide, aucune valeur à modifier si l'environnement correspond à ce schéma :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dakarleague_hub
DB_USERNAME=root
DB_PASSWORD=
```

Ajuster ces valeurs (et `APP_URL`) si le projet est installé ailleurs. Avec XAMPP, la base peut être créée en une commande :

```bash
"/chemin/vers/xampp/mysql/bin/mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS dakarleague_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3.2 Fichiers uploadés

Les logos de compétitions/équipes, photos de joueurs et PDF exportés sont stockés sur le disque `public` (`storage/app/public`), accessibles via le lien symbolique `public/storage` créé à l'installation.

## 4. Lancement

```bash
# Migrations + jeu de données de démonstration (compétition complète : équipes, joueurs, calendrier, résultats)
php artisan migrate:fresh --seed

# Build des assets front (Tailwind CSS + Alpine.js via Vite)
npm run build          # build de production
npm run dev            # ou : serveur de développement avec rechargement à chaud
```

En local avec XAMPP (Apache), démarrer Apache et MySQL depuis le panneau de contrôle XAMPP, puis ouvrir :

```
http://localhost/stage_defar/fin_stage/public/
```

Alternative sans XAMPP (serveur intégré Laravel) :

```bash
php artisan serve
```

## 5. Comptes de démonstration

Le seeder (`database/seeders/CompetitionDemoSeeder.php`) crée une compétition complète — *Championnat Ligue Amateur de Dakar* (6 équipes, 66 joueurs, 15 matchs dont 9 déjà joués avec buts/cartons cohérents) — ainsi que deux comptes fixes :

| Rôle | Email | Mot de passe |
|---|---|---|
| Super-administrateur | `admin@dakarleague.sn` | `password` |
| Organisateur | `organisateur@dakarleague.sn` | `password` |

Les responsables d'équipe et joueurs sont générés avec des emails aléatoires (factory) ; un compte **responsable** ou **joueur** réel s'obtient via `/register` puis, pour un responsable, en inscrivant une équipe depuis la page publique d'une compétition en statut *inscriptions ouvertes* (le rôle est alors automatiquement promu).

## 6. Architecture du projet

Architecture **MVC** standard Laravel, complétée par une couche **services** pour isoler la logique métier complexe (conforme à la décision de conception du cahier des charges, §3.1) :

| Couche | Rôle |
|---|---|
| `app/Models` | Entités Eloquent et leurs relations (Competition, Team, Player, GameMatch, MatchEvent, Lineup, Document, ActivityLog, User) |
| `app/Services` | `CalendarService` (génération du calendrier round-robin aller simple/aller-retour), `StandingsService` (calcul du classement et des critères de départage), `CompetitionStatsService` (top buteurs, discipline) |
| `app/Policies` | Autorisations par rôle et par compétition (CompetitionPolicy, TeamPolicy, PlayerPolicy, GameMatchPolicy) — reflètent la matrice des droits du cahier des charges §2.3 |
| `app/Http/Controllers/Admin` | Espace organisateur/super-admin (compétitions, équipes, joueurs, calendrier, résultats, compositions, export PDF, journal) |
| `app/Http/Controllers/Public` | Pages publiques consultables sans connexion (RG11 : aucune donnée sensible exposée) |
| `app/Http/Middleware/EnsureUserHasRole` | Middleware `role:...` limitant l'accès à l'espace admin selon le rôle |

**Le mot réservé `Match`** en PHP a imposé de nommer le modèle `GameMatch` (table `matches`).

### Rôles applicatifs

`super_admin`, `organisateur`, `responsable`, `joueur` (colonne `role` sur `users`) — le `visiteur` du cahier des charges correspond à un utilisateur non connecté.

## 7. Structure des dossiers

```
app/
  Http/Controllers/
    Admin/            Compétitions, équipes, joueurs, matchs, résultats, compositions, export PDF, journal
    Public/            Pages publiques + inscription en ligne d'équipe
    DashboardController.php   Aiguillage du tableau de bord selon le rôle
  Models/
  Policies/
  Services/
database/
  migrations/          12 migrations (users, competitions, teams, players, matches, match_events, lineups,
                        documents, activity_logs, competition_organizer, cache, jobs)
  factories/
  seeders/CompetitionDemoSeeder.php
docs/
  cahier-des-charges-dakarleague-hub.md
resources/views/
  admin/               Vues de l'espace organisateur (thème sombre DakarLeague Hub)
  public/competitions/ Pages publiques (compétition, classement, calendrier, résultats, statistiques, équipe, joueur)
  dashboard-responsable.blade.php / dashboard-joueur.blade.php
  layouts/, components/  Layout Breeze adapté à la charte graphique (fond noir, accent vert lime)
routes/web.php
```

## 8. Fonctionnalités et routes principales

| Domaine | Routes (préfixe) | Accès |
|---|---|---|
| Pages publiques | `/`, `/competitions`, `/competitions/{slug}/{classement,calendrier,resultats,statistiques,equipes/{team},joueurs/{player}}` | Visiteur (aucune connexion requise) |
| Inscription d'équipe | `/competitions/{slug}/inscription` | Utilisateur connecté, compétition en *inscriptions ouvertes* |
| Tableau de bord | `/dashboard` (aiguille vers l'espace adapté au rôle) | Utilisateur connecté |
| Espace organisateur | `/admin/...` (compétitions, équipes, joueurs, calendrier, résultats, compositions, export PDF, journal) | `super_admin`, `organisateur` (+ `responsable` pour ses équipes autorisées) |

Liste exhaustive : `php artisan route:list`.

### Fonctionnalités couvertes

- Authentification et rôles (Laravel Breeze, stack Blade)
- Compétitions, équipes, joueurs, calendrier généré automatiquement (round-robin aller simple/aller-retour)
- Saisie de résultats avec buts/cartons, validation refusée si incohérente avec le score (RG05)
- Classement automatique avec critères de départage : points, différence de buts, buts marqués, fair-play, confrontation directe (RG09)
- Pages publiques (compétition, classement, calendrier, résultats, statistiques, équipe, joueur)
- Export PDF du calendrier et du classement (DomPDF), archivé et tracé
- Statistiques (top buteurs, discipline, meilleure attaque/défense)
- Journal d'activité (traçabilité RG10)
- Fonctionnalités secondaires : compositions d'équipe (titulaires/remplaçants), inscription en ligne d'équipes avec validation par l'organisateur

## 9. Tests manuels effectués

Le projet ne dispose pas encore de suite de tests automatisés (PHPUnit/Pest). La validation s'est faite par des scénarios HTTP réels (connexion, création, soumission de formulaires), avec vérification systématique de l'état en base de données. Le détail des scénarios rejoués, leurs résultats et les anomalies détectées puis corrigées sont consignés dans le [cahier de tests](docs/cahier-de-tests.md).

## 10. Déploiement

Pour un déploiement sur un hébergement PHP/MySQL classique ou un VPS :

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build

cp .env.example .env   # puis configurer les variables de production
php artisan key:generate

php artisan migrate --force
php artisan storage:link

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Points d'attention :
- Le document root du serveur web doit pointer vers le dossier `public/`.
- `APP_ENV=production` et `APP_DEBUG=false` dans `.env`.
- `SESSION_SECURE_COOKIE=true` dès que le site est servi en HTTPS (le cookie de session n'est alors transmis que sur connexion chiffrée).
- `APP_URL` doit correspondre à l'URL publique réelle (utilisée pour les liens dans les pages, les exports PDF et les emails de vérification).
- Les dossiers `storage/` et `bootstrap/cache/` doivent être accessibles en écriture par le processus PHP.
- Prévoir une sauvegarde régulière de la base de données et du dossier `storage/app/public` (documents exportés, logos, photos).

## 11. Dépannage

| Symptôme | Piste |
|---|---|
| Page blanche / erreur 500 | Consulter `storage/logs/laravel.log` |
| Styles Tailwind absents | Lancer `npm run build` (ou `npm run dev` en développement) |
| Logos/photos non affichés | Vérifier que `php artisan storage:link` a bien été exécuté |
| Erreur de connexion MySQL | Vérifier que le service MySQL de XAMPP est démarré et que `DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` sont corrects |
| Export PDF vide ou en erreur | Vérifier l'extension PHP `gd` ou `dom`, requise par DomPDF |
