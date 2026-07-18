# MISSION CLAUDE CODE — Plateforme OSC/ONG Guinée
## Phase 0 (fondations) + Phase 1 (Spec 01 : socle multi-tenant)

---

## 1. CONTEXTE PRODUIT

Tu travailles sur une plateforme SaaS multi-organisations de **gestion de projets et de suivi-évaluation (S&E) pour les OSC et ONG de Guinée**. Positionnement : l'outil de travail quotidien (tâches, activités, budget, indicateurs) dont le rapport bailleur est un sous-produit — pas un outil de reporting qu'on ouvre en fin de trimestre. Contexte d'usage : connexions 3G faibles, smartphones modestes, utilisateurs peu technophiles, interface en français.

Maître d'œuvre : KIDIANI SARLU (Ocisse, développeur senior Laravel/Filament, Conakry). Premier pilote pressenti : ABLOGUI.

**Documents de référence dans `/docs`** (les lire avant de coder, ils font foi) :
- `docs/cahier_charges_v2.md` — cahier des charges v2.0
- `docs/backlog_v1_moscow.md` — backlog V1 priorisé (10 epics)
- `docs/spec_01_socle_multitenant.md` — LA spécification à implémenter dans cette mission : 26 règles de gestion (RG-01 à RG-26), modèle de données, workflows, écrans, matrice de permissions, cas limites, critères de recette

⚠️ **Périmètre strict de cette mission : Phase 0 + Spec 01 uniquement.** Les modules Projets, Activités, Tâches, S&E, Budget, Kobo, Rapports ont leurs specs en cours de rédaction — ne PAS les implémenter, ne pas créer leurs tables ni leurs Resources « en avance ».

---

## 2. STACK IMPOSÉ (non négociable)

- **PHP 8.3+, Laravel 13** (dernière mineure)
- **Filament 5** (panel builder) — tout le front admin passe par Filament/Livewire/Alpine/Tailwind, pas de SPA
- **PostgreSQL 16 + PostGIS** (image `postgis/postgis:16-3.4` en dev via Sail/compose)
- **Redis** : cache, sessions, queues (Horizon)
- **FrankenPHP** comme runtime cible en production (image Docker) ; en dev, Sail classique accepté
- Tests : **Pest** · Analyse statique : **Larastan (niveau max praticable, ≥ 8)** · Style : **Pint**

### Packages Laravel
`spatie/laravel-permission` (mode teams, team = organization) · `spatie/laravel-medialibrary` · `spatie/laravel-activitylog` · `spatie/laravel-settings` · `spatie/laravel-backup` · `maatwebsite/excel` · `laravel/horizon`

### Plugins Filament (tous vérifiés compatibles v5)
Officiels : `filament/spatie-laravel-media-library-plugin`, `filament/spatie-laravel-settings-plugin`
Communautaires : `bezhansalleh/filament-shield` (rôles/permissions), `jeffgreco13/filament-breezy` (profil + 2FA), `pxlrbt/filament-activity-log`, `shuvroroy/filament-spatie-laravel-backup`, `shuvroroy/filament-spatie-laravel-health`, `leandrocfe/filament-apex-charts`, `saade/filament-fullcalendar`, `dotswan/filament-map-picker`, `devletes/filament-progress-bar`
(Plugins des modules futurs — kanban Flowforge, etc. — à NE PAS installer maintenant.)

---

## 3. DÉCISIONS D'ARCHITECTURE (verrouillées — ne pas remettre en cause)

1. **Multi-tenancy en base unique** : colonne `organization_id` partout ; double protection = tenancy Filament (`->tenant(Organization::class)`) + trait `BelongsToOrganization` (global scope) sur TOUS les modèles tenant — jobs, commandes, exports inclus. Jamais stancl/tenancy, jamais de DB par tenant.
2. **ULID** comme clés primaires de toutes les entités exposées (`HasUlids`) ; jamais d'auto-incrément dans une URL, un export ou une API.
3. **Deux panels Filament** : `app` (tenant, utilisateurs des organisations) et `admin` (super-admin plateforme, hors tenancy, opéré par KIDIANI).
4. **Rôles** : 7 rôles fixes via spatie/permission en mode teams — `admin`, `chef_projet`, `responsable_se`, `responsable_financier`, `agent_terrain`, `consultant`, `bailleur`. Un utilisateur = un rôle = une organisation (V1).
5. **`users.team_member_id` NOT NULL** : chaque compte a sa fiche `TeamMember` créée dans la même transaction (annuaire = union comptes + membres sans compte).
6. **RLS PostgreSQL non activée** (choix documenté dans la spec §3) — ne pas l'introduire.
7. **Référentiel géo national** : table unique `geo_units` (4 niveaux, P-codes COD-AB OCHA, parent_id, geom nullable avec **index GIST dès la migration**) + `localities` par organisation. Seeder à partir du XLSX COD-AB (fichier fourni dans `docs/data/` — sinon créer la structure + un seeder de démonstration sur 2 régions et le signaler).
8. **Contrôle d'état utilisateur frais** : middleware relisant statut/expiration depuis DB ou cache Redis ≤ 60 s (jamais la seule session) — contrainte FrankenPHP worker + Livewire.
9. **Files Horizon** : `default` (interactions utilisateur) et `low` (traitements lourds, futurs imports Kobo) — configurer dès maintenant.
10. Langue : **interface et messages 100 % en français** (fichiers de lang FR), code et commentaires en français acceptés, noms techniques (classes, tables) en anglais.

---

## 4. PHASE 0 — FONDATIONS (à livrer d'abord)

1. Projet Laravel 13 neuf, Sail avec services : app, `postgis/postgis:16-3.4`, redis, mailpit.
2. Installation et configuration de tout le stack §2 (Filament 5 : deux panels ; Horizon ; plugins listés).
3. Qualité : Pest configuré (avec base de test PostgreSQL), Larastan, Pint ; scripts composer `test`, `analyse`, `format`.
4. **CI GitHub Actions** : workflow `ci.yml` — Pint (check), Larastan, Pest avec service PostgreSQL — bloquant. (Le workflow de build/déploiement GHCR viendra plus tard, ne pas le créer.)
5. `README.md` : prérequis, installation, commandes, structure du projet.
6. Trait `BelongsToOrganization` + classe de base des modèles tenant + **premier test Pest d'isolation** prouvant le mécanisme.

## 5. PHASE 1 — SPEC 01 INTÉGRALE

Implémenter `docs/spec_01_socle_multitenant.md` complètement : les 26 RG, le modèle de données §3 (tel quel — organizations, users, invitations, team_members, tags, sectors, donors, geo_units, localities), les workflows §4, les 10 écrans §5, la matrice §6, les cas limites §7 avec leurs messages exacts en français.

Points de vigilance issus des revues (déjà dans la spec, à ne pas rater) :
- Invitation : lien signé 72 h, anti-énumération (message générique + notification interne à l'admin de l'échec réel), proposition de liaison à une fiche membre existante AVANT création, action « fusionner » avec réassignation des FK.
- 2FA : obligatoire admin/super-admin, **codes de secours générés et téléchargement imposé** à l'activation.
- Expiration consultant/bailleur : middleware + révocation de session immédiate.
- Dernier admin actif indésactivable ; organisation suspendue → connexion bloquée avec message.
- Accès d'assistance super-admin : 24 h, identifiant de session distinct dans l'audit, bandeau persistant côté organisation.
- Audit spatie/activitylog sur toutes les actions du socle.

**Critères de sortie = §8 de la spec** : les 8 critères de recette doivent être couverts par des tests Pest (la suite d'isolation multi-tenant est prioritaire : accès croisés URL/recherche/exports → 404 ou vide). Objectif ≥ 80 % de couverture sur le socle.

---

## 6. MÉTHODE DE TRAVAIL EXIGÉE

- Lis les 3 documents de `/docs` AVANT toute ligne de code ; en cas de contradiction, la **Spec 01 prime**, puis le backlog, puis le CDC. Si une ambiguïté réelle subsiste : pose la question, ne tranche pas silencieusement.
- Avance par commits atomiques et messages clairs (français ou anglais, cohérent) ; propose un plan de commits avant de commencer.
- Migrations : une par table, contraintes et index dans la migration (FK, uniques, GIST sur geom), jamais de modification d'une migration déjà commitée.
- Chaque RG implémentée doit être traçable : cite son numéro (RG-XX) dans le test qui la couvre.
- Pas de sur-ingénierie : pas de repositories/CQRS/events superflus — Laravel idiomatique, Actions simples quand utile.
- Pas de données réelles dans les seeders de dev (Faker, noms fictifs).
- Tout texte visible utilisateur passe par les fichiers de lang FR.

## 7. INTERDITS

- Implémenter ou préparer les modules hors Spec 01 (pas de table `projects`, etc.)
- Changer un choix du §3, ajouter des packages non listés sans le proposer explicitement
- localStorage/sessionStorage, SPA, API publique (rien de tout ça en V1 socle)
- Exposer un ID auto-incrémenté où que ce soit
- Baisser le niveau Larastan ou skipper des tests pour « aller plus vite »

## 8. LIVRABLE FINAL DE LA MISSION

Un dépôt où : `composer test` est vert (dont la suite d'isolation), la CI passe, les deux panels fonctionnent (parcours complet du critère de recette n°2 : création d'organisation → invitation admin → 2FA → invitation chef de projet → membres d'équipe → liaison), le seeder de démo crée 2 organisations peuplées permettant de vérifier l'isolation à l'œil nu, et le README permet à un second développeur de démarrer en < 30 minutes.

Commence par : (a) lire les docs, (b) me présenter ton plan d'exécution en étapes avec les points où tu auras besoin d'arbitrage, (c) attendre mon GO avant le scaffolding.
