# Cahier de tests — DakarLeague Hub

Ce document consigne les résultats des scénarios de recette définis au cahier des charges (§6), ainsi que les anomalies détectées et corrigées au cours du développement.

## 1. Méthode

En l'absence de suite de tests automatisés (PHPUnit/Pest), la recette a été effectuée par des **requêtes HTTP réelles** (connexion, soumission de formulaires, navigation) contre l'application servie par XAMPP/Apache, avec vérification systématique de l'état réel en base de données (via `php artisan tinker`) et du journal d'erreurs (`storage/logs/laravel.log`) après chaque scénario.

Environnement de test : PHP 8.2, Laravel 12, MySQL 8 (XAMPP), base `dakarleague_hub` réinitialisée (`migrate:fresh --seed`) avant chaque campagne de test pour repartir d'un état connu.

## 2. Résultats des scénarios de recette (§6 du cahier des charges)

| ID | Scénario | Résultat attendu | Résultat obtenu | Statut |
|---|---|---|---|---|
| CR01 | Création compétition | Champs obligatoires validés, compétition enregistrée | Soumission sans nom : rejetée (aucun enregistrement créé). Soumission complète : compétition créée (compteur passé de 1 à 2, pas 3) | ✅ Conforme |
| CR02 | Équipes et joueurs | Données créées, modifiables, correctement reliées | Équipe modifiée via le formulaire (`PUT /admin/teams/{id}`) ; les 11 joueurs restent liés après modification | ✅ Conforme |
| CR03 | Génération calendrier | Aucune équipe contre elle-même ; rencontres conformes au format | 6 équipes, format aller simple → 15 matchs générés (n×(n-1)/2), répartis sur 5 journées de 3 matchs ; contrainte SQL `CHECK (home_team_id <> away_team_id)` active | ✅ Conforme |
| CR04 | Conflit de créneau | Refus de deux matchs simultanés pour une même équipe | **Anomalie détectée et corrigée** (voir §3.1) — après correction : 2ᵉ match refusé quand une équipe est déjà engagée au même horaire, y compris à la création manuelle d'un match | ✅ Conforme (après correction) |
| CR05 | Saisie résultat | Scores/buts/cartons cohérents ; incohérences refusées | Score déclaré 2-1 avec un seul but saisi → rejeté, match reste `programmé`. Score 2-1 avec 2+1 buts cohérents (dont un penalty et un carton) → accepté, statut `terminé`, 4 évènements enregistrés | ✅ Conforme |
| CR06 | Classement | Points, buts, différence et rangs recalculés après validation | Après validation d'un résultat 2-1, `StandingsService` recalcule immédiatement points/BP/BC/différence/rang pour les deux équipes concernées | ✅ Conforme |
| CR07 | Modification post-validation | Réservée à l'organisateur habilité, action tracée | Score d'un match déjà validé modifié (2-2 → 9-0) par l'organisateur ; entrée `activity_logs` créée avec l'identité de l'utilisateur et la description exacte | ✅ Conforme |
| CR08 | Droits d'accès | Le visiteur ne peut ni créer ni modifier de données | Accès direct aux formulaires admin sans connexion → redirection (302) ; soumission POST/PUT sans session valide → rejetée (419, CSRF/session absente) ; aucun enregistrement créé | ✅ Conforme |
| CR09 | Pages publiques | Classement, matchs, équipes visibles sans connexion | Toutes les pages publiques (accueil, liste, fiche, classement, calendrier, résultats, statistiques, équipe, joueur) répondent 200 sans session ; aucune donnée sensible (email/téléphone/licence) trouvée dans le HTML rendu (RG11) | ✅ Conforme |
| CR10 | Export PDF | Calendrier/classement PDF généré et téléchargeable | Export testé pour les deux documents : réponse `application/pdf`, fichier valide, contenu extrait (`pdftotext`) correspondant aux données réelles (journées, scores, classement trié) ; document et export tracés en base | ✅ Conforme |
| CR11 | Responsive | Parcours essentiels sur mobile, tablette, ordinateur | Mise en page construite avec les classes responsive Tailwind (`sm:`, `lg:`) sur l'ensemble des vues ; **non vérifié visuellement sur appareils/émulateurs réels** dans le cadre de cette session (aucun outil de test navigateur disponible) | ⚠️ Partiel — à confirmer manuellement |
| CR12 | Qualité finale | Aucun bug bloquant dans le scénario de démonstration | Scénario de démonstration recommandé (§6.1) rejoué intégralement sans erreur bloquante ; lint PHP complet (`php -l`) sans erreur ; 11 entrées d'erreur en log, toutes documentées et liées aux anomalies du §3 (déjà corrigées) | ✅ Conforme |

### 2.1 Scénario de démonstration (§6.1) — rejoué intégralement

Créer une compétition → ajouter des équipes et joueurs → générer le calendrier → saisir un match terminé avec buts et cartons → vérifier le classement → consulter la page publique → tester un accès non autorisé → exporter le classement en PDF.

**Résultat : scénario complet exécuté sans erreur bloquante**, avec vérification en base à chaque étape (voir jeu de données de démonstration créé par `CompetitionDemoSeeder` : 1 compétition, 6 équipes, 66 joueurs, 15 matchs dont 9 déjà joués).

## 3. Anomalies détectées et corrigées

Quatre anomalies réelles ont été détectées lors des campagnes de test et corrigées avant validation finale.

### 3.1 Conflit de créneau non appliqué à la création manuelle d'un match (CR04)

- **Symptôme** : deux matchs pouvaient être créés manuellement à la même date/heure pour une même équipe.
- **Cause** : `MatchController::store()` n'appelait jamais `CalendarService::hasSchedulingConflict()` (seule la modification `update()` le faisait). De plus, la méthode elle-même échouait silencieusement pour un match non encore enregistré : la clause `where('id', '!=', $match->id)` comparait à `NULL` (id d'un modèle non persisté), ce qui invalidait toute la requête en SQL et faisait toujours retourner « aucun conflit ».
- **Correction** : ajout de `->when($match->exists, ...)` pour ne pas exclure d'ID quand le match n'existe pas encore, et appel de la vérification dans `store()` avant création.
- **Vérification** : deux tentatives de création à l'identique (même équipe, même horaire) → un seul match créé, la seconde tentative rejetée avec message d'erreur.

### 3.2 Contrôleurs : méthode `authorize()` indisponible (Laravel 12)

- **Symptôme** : erreur 500 sur toutes les pages utilisant `$this->authorize(...)` (fiche compétition, etc.).
- **Cause** : dans Laravel 12, le contrôleur de base généré par défaut n'inclut plus le trait `AuthorizesRequests` (changement de structure « streamlined » depuis Laravel 11).
- **Correction** : ajout du trait `Illuminate\Foundation\Auth\Access\AuthorizesRequests` à `App\Http\Controllers\Controller`.

### 3.3 Vue `admin.dashboard` manquante

- **Symptôme** : erreur 500 (`View [admin.dashboard] not found`) à la connexion d'un organisateur.
- **Cause** : route et contrôleur créés avant la vue correspondante.
- **Correction** : création de la vue `resources/views/admin/dashboard.blade.php`.

### 3.4 Navigation en erreur pour les visiteurs non connectés

- **Symptôme** : erreur 500 sur toutes les pages publiques (`Attempt to read property "name" on null`).
- **Cause** : `layouts/navigation.blade.php` appelait `Auth::user()->name` sans vérifier la présence d'un utilisateur connecté — bug critique car il rendait **toutes les pages publiques** inaccessibles, alors qu'elles doivent être consultables sans compte (RG, §2.4-F).
- **Correction** : encadrement des blocs dépendant de l'utilisateur connecté avec `@auth` / `@else` (lien connexion/inscription affiché aux visiteurs).

## 4. Audit de sécurité

Un audit manuel du code a été mené (revue systématique de l'autorisation sur chaque contrôleur, du mass assignment, des uploads, des sorties Blade, des requêtes SQL et de la configuration d'authentification). Trois failles réelles ont été détectées et corrigées.

### 4.1 Injection d'évènements de match hors périmètre (faille la plus sérieuse)

- **Faille** : lors de la saisie d'un résultat, `team_id` et `player_id` de chaque évènement (but/carton) n'étaient validés que comme des entiers quelconques — sans vérifier qu'ils correspondaient réellement aux deux équipes du match. Un organisateur légitime (autorisé sur SA compétition) pouvait donc soumettre un `team_id` ou un `player_id` appartenant à une équipe/compétition totalement différente. La faille était d'autant plus insidieuse qu'un évènement avec un `team_id` étranger n'était compté dans aucun des deux totaux de la vérification RG05, ce qui lui permettait de passer inaperçu tout en polluant les statistiques publiques (top buteurs) d'une autre compétition.
- **Correction** : `StoreMatchResultRequest` restreint désormais `team_id` aux deux équipes du match (`Rule::in`) et vérifie, pour chaque évènement portant un `player_id`, que ce joueur appartient bien à l'équipe déclarée sur cet évènement.
- **Vérification** : deux attaques testées (équipe hors match, puis joueur d'une autre équipe avec `team_id` correct) → les deux rejetées, aucune écriture en base ; le flux légitime (score + buts/cartons réels) fonctionne toujours normalement.

### 4.2 Même faille sur la composition d'équipe (lineups)

- **Faille** : les clés du tableau de composition (`home[{player_id}]=titulaire`) provenaient directement des noms de champs du formulaire, sans vérifier que le `player_id` appartenait réellement à l'équipe du match.
- **Correction** : `LineupController::update()` vérifie désormais que chaque `player_id` soumis appartient à l'équipe (domicile/extérieur) correspondante avant tout enregistrement.
- **Vérification** : soumission avec un joueur d'une autre équipe → rejetée (422), aucune composition enregistrée ; soumission légitime → acceptée normalement.

### 4.3 Upload de logos/photos acceptant le format SVG (XSS stocké potentiel)

- **Faille** : la règle de validation générique `image` de Laravel accepte le format SVG, qui peut contenir du JavaScript exécutable. Un logo ou une photo de joueur au format SVG piégé, une fois affiché sur une page publique, aurait pu exécuter du script dans le navigateur des visiteurs (XSS stocké).
- **Correction** : les trois formulaires d'upload (logo de compétition, logo d'équipe, photo de joueur) restreignent désormais les formats acceptés à `jpeg, jpg, png, webp` (`mimes:...`), excluant SVG et BMP.

### 4.4 Renforcements complémentaires (défense en profondeur)

- Le compte `is_active` (prévu pour désactiver un utilisateur) n'était jamais vérifié à la connexion — un compte marqué inactif aurait pu continuer à se connecter. `LoginRequest::authenticate()` exige désormais `is_active = true` pour que `Auth::attempt()` réussisse.
- La fiche joueur publique ne vérifiait pas que l'équipe du joueur était validée : un joueur d'une équipe encore *en attente* (inscription en ligne non validée par l'organisateur) restait consultable en devinant son identifiant. Un contrôle `$player->team->isApproved()` a été ajouté.
- Le formulaire de création manuelle d'un match listait toutes les équipes, y compris celles en attente de validation ; la liste et la validation serveur sont désormais restreintes aux équipes validées.

### 4.5 Points vérifiés sans anomalie

- **Mass assignment** : chaque contrôleur passe par un `FormRequest` avec une liste blanche explicite de champs (`$request->validated()`) — aucun `$request->all()` n'est utilisé dans le projet. Les champs sensibles (`role`, `created_by`, `manager_user_id`, `registration_status`, `validated_by`) ne sont jamais dérivés de l'entrée utilisateur mais fixés côté serveur.
- **Autorisation** : chaque action de création/modification/suppression appelle `$this->authorize(...)` avant toute écriture ; vérifié systématiquement sur les 9 contrôleurs de l'espace admin.
- **IDOR** : les ressources imbriquées (équipe → compétition, joueur → équipe, match → compétition) sont toujours vérifiées via la relation réelle en base (`$team->competition->isOrganizedBy($user)`), jamais via un identifiant transmis par le client.
- **XSS** : aucune sortie Blade non échappée (`{!! !!}`) dans l'ensemble du projet, y compris les vues PDF.
- **Injection SQL** : aucune requête brute avec entrée utilisateur interpolée (`DB::raw`/`whereRaw` non utilisés avec variable).
- **CSRF** : protection standard Laravel active sur toutes les routes web ; vérifiée par un rejet 419 lors d'une tentative de soumission sans session valide.
- **Brute-force** : le throttling de connexion par défaut de Breeze (5 tentatives, verrouillage progressif par couple email+IP) est intact et fonctionnel.
- **Cookies de session** : `HttpOnly` actif par défaut ; `SameSite=Lax` ; penser à activer `SESSION_SECURE_COOKIE=true` en production HTTPS (voir README, déploiement).

## 5. Contre-audit indépendant (4 agents en parallèle)

Pour ne pas se fier uniquement à l'auto-évaluation de l'audit du §4, une seconde campagne a été menée avec **4 agents indépendants exécutés en parallèle**, chacun sur un périmètre distinct (autorisation/IDOR, validation d'entrée/injection, authentification/sessions, exposition de données publiques), avec pour consigne explicite de ne pas faire confiance au résumé fourni et de re-vérifier chaque correctif par eux-mêmes, avec tests HTTP réels à l'appui.

**Les 3 correctifs du §4 ont été confirmés efficaces par les 4 agents**, avec de nouvelles tentatives d'exploitation (payloads XSS SVG réels, injection de `team_id`/`player_id` étrangers, etc.) — aucun contournement trouvé.

**4 nouvelles failles réelles ont été détectées et corrigées :**

### 5.1 Exports PDF accessibles anonymement (Critique)

- **Faille** : le nom de fichier des PDF exportés (`calendrier-{slug}-{saison}.pdf`) était entièrement prévisible et le fichier stocké sur le disque `public`, exposé sans authentification via `/storage/documents/...`. Un visiteur anonyme connaissant (ou devinant) le nom et la saison d'une compétition pouvait télécharger son calendrier/classement complet — y compris pour une compétition en statut **brouillon**, jamais censée être publique. Prouvé en conditions réelles : export du calendrier d'une compétition brouillon par l'organisateur, puis téléchargement anonyme réussi (200, fichier complet) via le nom deviné, alors que la compétition elle-même restait bien 404 sur toutes les routes publiques.
- **Correction** : les PDF sont désormais stockés sur le disque `local` (non servi publiquement — vérifié 403 en accès direct) avec un nom de fichier aléatoire de 40 caractères (`Str::random(40)`), indépendant du nom de la compétition. Les anciens fichiers déjà exposés sur le disque public ont été supprimés.

### 5.2 Suppression de compte détruisant des données partagées en cascade (Élevé)

- **Faille** : `competitions.created_by` utilisait `cascadeOnDelete()`. N'importe quel organisateur pouvait supprimer son propre compte via `/profile` (flux standard Breeze, mot de passe ressaisi) et emporter avec lui, instantanément et sans avertissement spécifique, toute compétition dont il est créateur — équipes, joueurs, matchs, résultats, classements publics inclus. Prouvé en conditions réelles par suppression de compte via le vrai flux HTTP.
- **Correction** : la suppression de compte est désormais bloquée avec un message explicite tant que l'utilisateur reste propriétaire d'au moins une compétition (`ProfileController::destroy`) ; la contrainte de base a également été durcie de `cascadeOnDelete()` à `restrictOnDelete()` en défense en profondeur. Testé : suppression refusée avec compétition active, puis acceptée après suppression de la compétition.

### 5.3 Équipe refusée après coup toujours visible publiquement (Moyen)

- **Faille** : `StandingsService` excluait déjà correctement les équipes non validées, mais le calendrier public, les résultats publics et le top buteurs/discipline (`CompetitionStatsService`) ne filtraient que par statut du match, jamais par statut d'approbation de l'équipe — une équipe validée puis refusée a posteriori (après avoir déjà joué des matchs) restait visible dans ces trois vues.
- **Correction** : `calendar()`/`results()` filtrent désormais les matchs dont les deux équipes sont approuvées ; `topScorers()`/`topCards()` filtrent désormais par équipe approuvée. Testé : équipe rejetée après un match joué avec but marqué → n'apparaît plus ni dans le calendrier, ni dans les résultats, ni dans les statistiques publiques.

### 5.4 Oracle de validation cross-compétition (Moyen)

- **Faille** : `StoreTeamRequest`, `StorePlayerRequest`, `StoreMatchResultRequest` et `StoreCompetitionRequest` avaient toutes `authorize(): bool { return true; }`, déléguant le contrôle d'accès réel à `$this->authorize(...)` **dans le corps de la méthode du contrôleur**. Or Laravel résout et valide un `FormRequest` injecté par type-hint **avant** d'exécuter le corps de la méthode — les règles de validation (qui interrogent la compétition/l'équipe ciblée par la route, sans vérifier les droits) s'exécutaient donc avant le contrôle d'autorisation. Un organisateur authentifié pouvait ainsi sonder, via les messages d'erreur de validation (ex. « ce nom d'équipe existe déjà »), le contenu d'une compétition qu'il ne gère pas — sans pouvoir y écrire (l'écriture restait bien bloquée par le contrôleur).
- **Correction** : la vérification d'autorisation réelle a été déplacée dans la méthode `authorize()` de chacun de ces 4 `FormRequest`, en s'appuyant sur les mêmes policies que les contrôleurs (`$this->user()->can('update', $team)`, etc.), afin qu'elle s'exécute avant toute requête de validation. Testé : la même sonde qui renvoyait un 422 avec le message de fuite renvoie désormais un 403 immédiat, sans aucune information sur le contenu de la compétition ciblée.

### 5.5 Renforcements complémentaires (défense en profondeur)

- La désactivation d'un compte (`is_active=false`) ne coupait que les *nouvelles* connexions ; une session déjà ouverte restait valide jusqu'à expiration naturelle. Un middleware `EnsureAccountIsActive`, appliqué à tout le groupe `web`, coupe désormais immédiatement toute session dont le compte est désactivé entre-temps. Testé en conditions réelles : accès à `/dashboard` avant/après désactivation avec le même cookie de session → 200 puis 403 immédiat.
- Le middleware `verified` était appliqué sur les routes `/dashboard` et `/admin/*` alors que `User` n'implémente pas `MustVerifyEmail` — il ne bloquait donc jamais rien, en donnant une fausse impression de protection. Retiré des routes pour refléter honnêtement le comportement réel (aucune vérification d'e-mail obligatoire n'était de toute façon exigée par le cahier des charges).
- `role` et `is_active` ont été retirés de `User::$fillable` (ils ne doivent jamais pouvoir être définis par mass assignment) ; le seul point du code qui en dépendait (promotion automatique joueur → responsable lors d'une inscription d'équipe) a été adapté pour utiliser une affectation directe (`$user->role = ...; $user->save();`). Testé : la promotion fonctionne toujours normalement après ce changement.

## 6. Limites connues

- Pas de suite de tests automatisés (unitaires/fonctionnels) à ce jour — recommandé en évolution (cf. cahier des charges §7 et présente documentation, section « limites »).
- CR11 (responsive) validé par construction (classes Tailwind) mais pas par test visuel réel sur périphériques — à confirmer avant démonstration finale.
- La confrontation directe (RG09, dernier critère de départage) utilise une comparaison par paires : correcte pour une égalité entre deux équipes, mais peut ne pas produire un ordre unique en cas d'égalité cyclique à 3 équipes ou plus (limitation documentée dans le code, `StandingsService`).
