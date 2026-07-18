# CAHIER DES CHARGES
## Plateforme de Gestion de Projets et de Suivi-Évaluation pour les OSC et ONG en Guinée

**Version 2.1 — Juillet 2026** (remplace la v1.0 de septembre 2025 et la v2.0 de juillet 2026)
**Stack :** Laravel 13 · Filament 5 · PostgreSQL/PostGIS · Kobo Toolbox
**Maître d'œuvre :** KIDIANI SARLU

---

## Note sur cette version

La v2.0 était une refonte de la v1.0 suite à une revue critique multi-rôles : périmètre resserré sur la chaîne de valeur (planifier → exécuter → suivre → rapporter) au lieu du périmètre ERP initial ; comptabilité, RH complète et intégrations bancaires remplacées par des équivalents légers ; choix ouverts tranchés (multi-tenancy base unique, PostgreSQL, Kobo, hébergement existant) ; stack actualisé et CI/CD spécifiée ; sections exploitation fondées sur une infrastructure réelle et testée.

**La v2.1 ajoute**, suite à deux revues externes convergentes : la section 1.5 (proposition de valeur et positionnement), la correction du planning (§8.2 — charge revue à la hausse suite à l'enrichissement du périmètre), et l'alignement sur les décisions actées depuis (Filament 5 confirmé, kanban/commentaires/étiquettes, accès bailleur, tâches indépendantes récurrentes, rapport narratif Word, SMS rétrogradé).

---

## 1. Contexte et objectifs

### 1.1 Contexte
Les OSC et ONG guinéennes gèrent simultanément plusieurs projets financés par des bailleurs différents, chacun imposant ses formats de planification et de rapportage. La gestion repose majoritairement sur des outils dispersés (Excel, papier, WhatsApp) : consolidation laborieuse, redevabilité fragile, temps considérable perdu. La plateforme outille ces organisations pour la gestion de **leurs propres** projets — ce n'est pas un outil de supervision gouvernementale.

### 1.2 Objectifs de la V1
- Planification annuelle des projets et activités par organisation.
- Suivi de l'exécution depuis le terrain, y compris hors connexion via Kobo Toolbox.
- Mesure des indicateurs (réalisé vs cible) avec désagrégation.
- Suivi budgétaire par projet et par ligne, sans comptabilité lourde.
- Production rapide des rapports attendus par les bailleurs (Excel/PDF/Word).
- Gestion quotidienne de l'organisation : tâches (y compris hors projet), kanban, commentaires, rappels.

### 1.3 Utilisateurs cibles
Directeurs et coordonnateurs, chefs de projet, responsables S&E, responsables financiers, agents de terrain et animateurs communautaires, consultants ponctuels, bailleurs invités en lecture seule.

### 1.4 Question à trancher avant le développement : portage et modèle économique
Une OSC guinéenne moyenne ne peut pas financer seule l'exploitation d'une telle plateforme. Le modèle réaliste : plateforme mutualisée portée par une structure faîtière, un consortium ou un bailleur. Le montage institutionnel (qui porte, qui finance l'exploitation, plan de pérennité) doit être formalisé pendant la phase de cadrage — **condition bloquante** du lancement du développement. Premier pilote pressenti : ABLOGUI ; 2 à 4 OSC pilotes complémentaires à recruter (diversité : grande ONG multi-bailleurs, petite OSC de terrain, structure hors Conakry).

### 1.5 Proposition de valeur et positionnement
**Positionnement en une phrase :** l'outil de travail *quotidien* des OSC guinéennes — là où les alternatives internationales sont des outils de reporting ouverts en fin de trimestre, cette plateforme est ouverte chaque matin (tâches, activités, budget), et le rapport bailleur devient un sous-produit du travail quotidien.

**Différenciateurs :**
1. **Rapport narratif pré-rempli (Word)** : structure du canevas, tableaux d'indicateurs et de budget remplis, photos insérées — aucune alternative ne livre le document réellement transmis au bailleur. Métrique pilote : temps de production d'un rapport trimestriel avant/après.
2. **Gestion quotidienne intégrée** (tâches, kanban, rappels, commentaires) : crée l'habitude d'usage, donc la saisie S&E au fil de l'eau et une meilleure qualité de données par effet de bord.
3. **Prix et modèle** : mutualisation portée par une faîtière → coût marginal par OSC proche de zéro, en GNF, sans carte bancaire internationale (vs milliers de $/an de DevResults, centaines d'€/mois d'ActivityInfo).
4. **Ancrage guinéen** : découpage COD-AB natif jusqu'au district + localités propres, bailleurs et ministères locaux, canevas des bailleurs actifs en Guinée, support français de proximité, formation en présentiel.
5. **Fenêtre bailleur** : accès lecture seule offert par l'ONG sur les projets qu'elle choisit — transparence volontaire, argument de levée de fonds.
6. **Souveraineté** : code maîtrisé localement, conformité pensée pour la loi guinéenne L/2016/037, réversibilité totale (exports complets).

**Ce que la plateforme n'est pas (assumé)** : pas un outil de collecte (Kobo, intégré, le fait mieux) ; pas une plateforme statistique nationale (segment DHIS2) ; pas un ERP (la comptabilité reste chez le comptable). Honnêteté de positionnement : ActivityInfo et DevResults restent plus riches en S&E avancé — la cible ici est le segment que ces solutions ne servent pas (prix, devise, langue, support). **Le concurrent n°1 est Excel + WhatsApp + papier** : les arguments décisifs contre lui sont la consolidation automatique, la traçabilité, les désagrégations sans erreur et la survie des données au départ d'un chargé de projet — d'où une exigence UX forte (moins de clics qu'un tableur). Un benchmark détaillé (ActivityInfo, DevResults, DHIS2, CommCare, TolaData) est un livrable du cadrage.

---

## 2. Décisions structurantes et périmètre

### 2.1 Architecture multi-tenant : base unique
SaaS multi-organisations en base unique, `organization_id` sur toute donnée métier. Isolation à deux niveaux : tenancy native Filament (`->tenant(Organization::class)`) + global scope Eloquent (`BelongsToOrganization`) couvrant jobs, commandes, API, exports. Approche « une base par tenant » (stancl/tenancy) explicitement écartée ; tout on-premise avec synchronisation écarté — la résilience aux coupures est traitée côté collecte (Kobo hors-ligne natif), pas côté serveur.

### 2.2 Gestion financière : suivi budgétaire léger, pas de comptabilité
La comptabilité générale reste dans les outils du comptable. La plateforme couvre le suivi budgétaire projet : lignes budgétaires (rubriques), répartition par bailleur en cofinancement ; dépenses simples rattachées à une ligne et une activité avec justificatif scanné ; tableau budget vs engagé vs dépensé vs disponible avec taux et alertes ; exports Excel.

### 2.3 Ressources humaines : modèle léger
Pas de module RH. Comptes utilisateurs à profil léger + entité **membre d'équipe sans compte** (animateur assignable, saisie par le chef de projet ; liable ultérieurement à un compte sans perte d'historique) ; affectation aux projets, assignation des activités/tâches, vue « mes tâches ».

### 2.4 Collaboration quotidienne (ajout v2.1)
Tâches rattachées à un projet/une activité **ou indépendantes** (vie de l'organisation : administratif, renouvellements), avec récurrence et rappels ; vue kanban ; commentaires avec mentions @ (scopées à l'organisation) ; étiquettes fermées définies par l'admin ; pièces jointes sur tâches.

### 2.5 Hors périmètre V1
Comptabilité générale et RH complètes · intégrations bancaires et mobile money · module d'enquêtes maison (remplacé par Kobo) · application mobile native (V2 si le pilote le justifie ; stack pressenti Flutter) · interfaces écrites en langues nationales (V1 : français simple ; audio/pictogrammes à l'étude en V2) · devis/factures émis (V2, déclinaison entreprise si confirmée) · sous-tâches et dépendances de tâches · GED avancée · flex fields.

---

## 3. Spécifications techniques

### 3.1 Stack retenu
| Composant | Choix | Justification |
|---|---|---|
| Backend | Laravel 13 (PHP 8.3+) | Version courante (mars 2026), support 2028, expertise équipe |
| Admin / UI | **Filament 5** | Multi-tenancy natif ; compatibilité des plugins critiques vérifiée |
| Frontend | Livewire 3 + Alpine + Tailwind | Fournis par Filament ; pas de SPA |
| Base de données | PostgreSQL 16 + PostGIS | Géolocalisation, JSONB (Kobo, métadonnées) |
| Cache / files | Redis + Horizon | Files `default` et `low` (imports Kobo isolés) |
| Collecte terrain | Kobo Toolbox (API v2) | Hors-ligne natif, standard du secteur |
| Chronogramme | Frappe Gantt (JS) | Intégration Livewire/Alpine ; pas de Gantt custom |
| Kanban | Relaticle Flowforge | Vue kanban des tâches |
| Exports | laravel-excel, dompdf/browsershot, PHPWord | Excel, PDF, rapport narratif Word |
| Runtime prod | FrankenPHP / Caddy en conteneur | Pattern éprouvé sur les apps existantes |

### 3.2 Packages structurants
spatie : laravel-permission (teams), medialibrary (compression adaptée aux connexions faibles), activitylog, settings, backup · laravel-excel · Horizon.

### 3.3 Plugins Filament (compatibilité v5 vérifiée)
Officiels : media-library, settings. Communautaires : Shield (rôles), Breezy (profil + 2FA + codes de secours), FullCalendar, Map Picker (Leaflet/OSM), Apex Charts, Backup UI, Health UI, Activity Log, Progress Bar, Flowforge (kanban), Commentions ou équivalent (commentaires + mentions — compatibilité à confirmer, sinon développement Livewire dédié).

### 3.4 Intégration Kobo Toolbox
Formulaires conçus dans Kobo par les responsables S&E ; job planifié (file `low`, 1 worker) interrogeant l'API v2, import **idempotent**, rattachement aux activités/indicateurs mappés (mapping configurable sans code), journal des imports et rejets.

### 3.5 Rôles applicatifs
7 rôles par organisation : admin, chef de projet, responsable S&E, responsable financier, agent de terrain, consultant (expirable), **bailleur (lecture seule, expirable, projets explicitement partagés uniquement)**. Super-admin plateforme hors tenant (opéré par KIDIANI), sans accès aux données métier par défaut (accès d'assistance 24 h, tracé, visible par l'organisation).

---

## 4. Fonctionnalités de la V1
Le détail fait foi dans le **backlog V1 (MoSCoW)** et les **spécifications fonctionnelles par module**. Synthèse : organisations et référentiels (étiquettes, secteurs, bailleurs, géographie nationale COD-AB 4 niveaux + localités par organisation) ; projets avec cycle de vie, cadre logique simplifié, chronogramme Gantt, équipe, vue portefeuille, partage bailleur ; activités (planification, réalisations désagrégées à date de réalisation distincte de la saisie, justificatifs compressés, géolocalisation, formulaires papier imprimables) ; tâches et collaboration (kanban, commentaires/mentions, étiquettes, tâches indépendantes récurrentes, pièces jointes) ; S&E (indicateurs baseline/cibles/réalisé désagrégés, multi-cadres de résultats par bailleur, registre des bénéficiaires anti-double-comptage, moyens de vérification) ; budget (cf. §2.2) ; Kobo (cf. §3.4) ; rapports (activités, indicateurs, financier ; **3 canevas bailleurs max en V1** ; **rapport narratif Word pré-rempli**) ; tableaux de bord (direction, chef de projet) et notifications (in-app + e-mail ; SMS en Could).

---

## 5. Exigences non fonctionnelles

### 5.1 Performance et sobriété réseau
Pages principales < 2 s en 3G ; pagination, compression d'images, cache. Dimensionnement initial : dizaines d'organisations, ~100 utilisateurs simultanés — révisé après pilote sur mesures.

### 5.2 Sécurité
OWASP (XSS, CSRF, injection), HTTPS, chiffrement des données sensibles ; **ULID sur tout identifiant exposé** ; isolation inter-organisations couverte par une suite de tests dédiée ; 2FA obligatoire pour les admins avec codes de secours ; expiration des comptes temporaires contrôlée par middleware avec révocation de session (état relu depuis DB/cache ≤ 60 s — contrainte FrankenPHP/Livewire) ; audit complet ; scan antivirus asynchrone des uploads et classification des données (spec transverse dédiée) ; RLS PostgreSQL non activée en V1 (choix documenté, réactivable).

### 5.3 Cadre légal des données personnelles
Référentiel applicable : **loi guinéenne L/2016/037** (cybersécurité et protection des données personnelles), pas le RGPD. Données de bénéficiaires : minimisation, accès par rôle, journalisation, champs nominatifs chiffrés au niveau applicatif et absents des rapports/exports (identifiants abstraits et agrégats seulement). Localisation d'hébergement à valider juridiquement au cadrage ; exigences spécifiques des bailleurs recensées au cadrage.

### 5.4 Qualité logicielle
Pest (couverture ≥ 80 % du cœur métier), Larastan et Pint bloquants en CI ; interface 100 % français ; conventions Laravel/Filament standard ; Definition of Done formalisée au cadrage.

---

## 6. Hébergement, sauvegarde et exploitation

### 6.1 Infrastructure cible
Infrastructure Docker existante de l'équipe (VPS 6 vCPU / 12 Go RAM, Ubuntu 24.04) hébergeant déjà plusieurs productions Laravel selon un pattern éprouvé : 3 conteneurs par environnement (app FrankenPHP, queue, scheduler) ; reverse proxy Nginx Proxy Manager mutualisé (TLS auto) ; Redis mutualisé ; **conteneur PostGIS dédié** à la plateforme (postgis/postgis:16), bases séparées staging/production ; deux environnements complets (staging = données fictives + démos aux pilotes).

### 6.2 Sauvegardes (dispositif opérationnel)
En place et **testé** : script nocturne (cron) — dumps compressés de chaque base PostgreSQL, archives des volumes applicatifs et de la configuration proxy/certificats — envoyés vers Cloudflare R2, rotation 7 quotidiennes + 4 hebdomadaires ; restauration validée de bout en bout, test reconduit mensuellement ; les bases de la plateforme seront couvertes automatiquement. RPO 24 h, RTO < 4 h.

### 6.3 Supervision
Sentry (erreurs), Uptime Kuma (disponibilité), healthchecks Docker, plugin Health dans le panel super-admin, alerte en cas d'échec du backup.

### 6.4 Points d'attention
Localisation de l'hébergement (datacenter européen) à valider au cadrage (loi L/2016/037, exigences bailleurs) — migration vers un hébergeur régional possible sans changement d'architecture. À documenter en exploitation : politique de rétention des pièces jointes, archivage, trajectoire de montée en charge.

---

## 7. Chaîne d'intégration et de déploiement (CI/CD)
**CI (GitHub Actions)** : à chaque push — Pest (avec PostgreSQL de test), Larastan, Pint ; tout échec bloque. S'exécute chez GitHub, zéro charge sur le serveur. **CD** : si CI verte, build de l'image Docker par Actions, publication sur GHCR — le serveur ne compile jamais ; déploiement par script SSH (compose pull, up -d, migrate --force, purge caches, redémarrage queues) ; staging automatique à la fusion, **production manuelle** (workflow_dispatch) après validation staging ; rollback = redéploiement de l'image précédente (versionnée). Traçabilité complète des mises en production (atout d'audit bailleurs).

---

## 8. Démarche de réalisation et planning

### 8.1 Démarche
Cadrage complet avant code : backlog MoSCoW puis **spécifications fonctionnelles par module** (règles de gestion numérotées, modèle de données, workflows, écrans, permissions, cas limites, critères de recette) — le développement est de l'exécution, pas de l'interprétation. Développement itératif : sprints de 2 semaines, démonstration de chaque incrément aux OSC pilotes sur staging, maquettes/prototypes testés avec de vrais utilisateurs (dont agents de terrain) avant de figer les écrans. Ordre des specs : 01 Socle · 02 Projets/cadre logique · 03 Activités · 04 Tâches/collaboration · 05 S&E/bénéficiaires · 06 Budget · 07 Kobo · 08 Rapports/dashboards/notifications · 09 Administration.

### 8.2 Phasage (révisé v2.1)
| Phase | Durée | Contenu et jalon |
|---|---|---|
| Cadrage | 6–8 semaines | Ateliers OSC pilotes ; montage institutionnel et modèle économique (**bloquant**) ; canevas bailleurs (3 max) ; référentiels ; maquettes testées ; specs fonctionnelles ; DoD ; registre de risques + RACI. Jalon : go/no-go développement. |
| Développement MVP | **6–7 mois (1 dev senior) ou 4–5 mois (2 devs)** — charge revue à la hausse suite à l'enrichissement du périmètre (kanban, commentaires, accès bailleur, rapport narratif) | Socle multi-tenant → modules par ordre des specs, CI/CD dès la semaine 1. Jalon : recette staging par les pilotes. |
| Pilote | 2–3 mois | Usage réel 3–5 organisations ; formation ; mesures d'usage et de temps gagné. Jalon : go/no-go extension. |
| Extension | continu | Nouvelles organisations ; backlog V2 (mobile Flutter, audio/pictogrammes, SMS, déclinaisons entreprise/ministère) selon le pilote. |

### 8.3 Équipe et risques
Équipe minimale : 1 développeur senior Laravel/Filament + 1 profil S&E/métier (cadrage et recettes) ; **risque « facteur bus » assumé et mitigé** (specs détaillées, documentation, CI/CD, revues) — second développeur recommandé dès que financé. Risques suivis : adoption terrain (pilotes, circuit papier/Kobo, UX « moins de clics qu'Excel ») ; pérennité financière (cadrage, bloquant) ; canevas bailleurs (plafond 3) ; connectivité (sobriété + Kobo) ; quota SMS par organisation si activé.

---

## 9. Livrables et critères d'acceptation

### 9.1 Livrables
Code source (dépôt privé, CI/CD opérationnelle) ; plateforme en staging et production ; documentation (installation/exploitation, guides par rôle, modèles Kobo, modèles d'exports) ; formation des pilotes et transfert à la structure porteuse.

### 9.2 Critères d'acceptation (mesurables)
1. Parcours complet démontré (projet → activités → réalisations dont Kobo → indicateurs → rapport bailleur) en moins de temps que le processus actuel des pilotes.
2. Isolation inter-organisations vérifiée par tests automatisés dédiés.
3. Couverture ≥ 80 % du cœur métier, CI verte sur la branche principale.
4. Pages principales < 2 s en 3G simulée.
5. Restauration complète d'une sauvegarde validée en conditions réelles.
6. Tests d'utilisabilité pilotes : ≥ 80 % de réussite des tâches clés sans assistance.

### 9.3 Maintenance et évolution
Maintenance corrective et sécurité 6 mois post-production ; dépendances au fil de l'eau via CI ; évolutions V2 arbitrées sur les mesures d'usage du pilote.

---

## 10. Points restant à trancher au cadrage
| Point | Décision attendue | Échéance |
|---|---|---|
| Portage institutionnel | Structure porteuse et financement de l'exploitation | Fin du cadrage — **bloquant** |
| OSC pilotes | 3–5 organisations diverses (ABLOGUI acquis) | Début du cadrage |
| Canevas bailleurs | Les 3 formats V1 | Fin du cadrage |
| Localisation d'hébergement | Validation juridique L/2016/037 + exigences bailleurs | Fin du cadrage |
| Référentiels seed | Secteurs ; bailleurs (internationaux actifs en Guinée + ministères sectoriels) | Ateliers |
| Nom du produit | Choix + vérification linguistique (poular/malinké/soussou), domaine, OAPI | Cadrage |

---

*Validation — Date : ______ · Structure porteuse : ______ · Maître d'œuvre (KIDIANI SARLU) : ______*
