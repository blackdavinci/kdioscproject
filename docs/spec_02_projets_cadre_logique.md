# SPEC 02 — Projets et cadre logique

**Version :** 1.0 — Juillet 2026 · **Réf. :** CDC v2.0, Backlog V1 (Epic 2, stories 2.1–2.7)
**Statut :** à valider avant développement

---

## 1. Objectif et périmètre

Ce module introduit l'entité centrale de la plateforme : le **projet**, son **cadre logique** (objectifs → résultats → activités), son **cycle de vie**, son **équipe**, ses **bailleurs et montants**, sa **zone d'intervention**, ainsi que le **partage en lecture seule à un bailleur**. Il s'appuie sur le socle (Spec 01) : organisation, comptes/rôles, membres d'équipe, référentiels (secteurs, bailleurs), géographie (geo_units + localités).

**Couvre :** stories 2.1 à 2.7, **y compris le chronogramme Gantt (2.5) et la vue portefeuille (2.6)** intégrés dès cette spec.
**Ne couvre pas :** la planification et la réalisation terrain des activités (Spec 03), les tâches/kanban (Spec 04), la définition et la saisie des **valeurs** d'indicateurs et les cadres de résultats multi-bailleurs (Spec 05), le budget détaillé et les dépenses (Spec 06), les rapports (Spec 08). La **gestion documentaire avancée** (2.8) est V2.

**Frontière logframe ↔ terrain :** le nœud « activité » du cadre logique est la **définition planifiée** d'une activité. Les occurrences terrain (planification datée, réalisations, participants) de la Spec 03 **référencent** ce nœud. Les indicateurs (Spec 05) s'**attachent** à n'importe quel nœud du cadre logique (rattachement polymorphe préparé ici, valeurs en Spec 05).

---

## 2. Règles de gestion

### Projet — identité et périmètre
- **RGP-01** — Un projet appartient à exactement une organisation (`organization_id`, global scope hérité du socle). Champs d'identification : **titre**, **code** (unique par organisation, saisi ou proposé), description, secteur(s) d'intervention (référentiel Spec 01), groupes cibles (texte structuré), **période** (date de début / date de fin, début ≤ fin).
- **RGP-02** — **Zone d'intervention** : une ou plusieurs unités géographiques du référentiel national (`geo_units`, tous niveaux) et/ou localités d'organisation (`localities`). Aucune donnée personnelle de bénéficiaire à ce stade (Spec 05).
- **RGP-03** — **Bailleurs et montants** : un projet a zéro, un ou plusieurs bailleurs (cofinancement). Pour chaque bailleur : montant en **GNF** (devise de travail unique), et facultativement un montant en devise d'origine + code devise, **à titre informatif** (pas de conversion, cf. CDC §2.2). Le bailleur est choisi dans le référentiel bailleurs de l'organisation (Spec 01, RG-20).
- **RGP-04** — Objectifs, résultats attendus et activités sont portés par le **cadre logique** (RGP-08 à RGP-11), pas par des champs libres du projet.

### Cycle de vie
- **RGP-05** — Statuts : `brouillon` → `validé` → `en_cours` → `clôturé`, avec `suspendu` atteignable depuis `validé` ou `en_cours` et réversible vers l'état antérieur. Transitions autorisées :
  - `brouillon → validé` (validation) ; `validé → en_cours` (démarrage) ; `en_cours → clôturé` (clôture) ; `{validé, en_cours} ⇄ suspendu` ; `brouillon → (supprimé/archivé)`.
  - Aucune transition arrière depuis `clôturé` (état terminal ; une réouverture éventuelle = action admin explicite, hors V1).
- **RGP-06** — La **validation** et les transitions sont soumises aux permissions (§6). **Pas de séparation des rôles imposée** : dans une petite structure, un profil `admin` (ou chef de projet habilité) peut valider ses propres projets. Chaque changement de statut exige un **motif** pour `suspendu` et `clôturé`, et est **journalisé** (auteur, ancien/nouveau statut, motif, horodatage) — historique consultable sur le projet.
- **RGP-07** — Effets des statuts : un projet `brouillon` n'est visible que de son équipe et des admins ; `suspendu` et `clôturé` passent le projet et son cadre logique en **lecture seule** (plus de saisie d'activités/indicateurs/dépenses rattachées), les données restant intégralement consultables. La réactivation d'un `suspendu` rétablit l'écriture.

### Cadre logique
- **RGP-08** — Le cadre logique est un **arbre** rattaché au projet. Types de nœuds : `objectif_general`, `objectif_specifique`, `resultat`, `activite`. Hiérarchie attendue : objectif général › objectifs spécifiques › résultats › activités. L'arbre tolère un objectif général optionnel (petits projets) ; au minimum un résultat portant des activités.
- **RGP-09** — Chaque nœud porte : `code` (numérotation type OS1, R1.1, A1.1.1), `intitulé`, description, `position` (ordre entre frères). Le code est **proposé automatiquement** à la création selon la position, **modifiable à la main**, et **non recalculé** lors d'un réordonnancement/déplacement (stabilité des références citées en rapport/doc bailleur). Une action facultative « Renuméroter » réaligne les codes sur l'ordre courant à la demande. Réordonnancement et re-rattachement (glisser dans l'arbre) autorisés tant que le projet n'est pas clôturé.
- **RGP-10** — **Indicateurs** : tout nœud peut porter des indicateurs (rattachement polymorphe `indicatable` préparé ici). La **définition** et les **valeurs** relèvent de la Spec 05 ; en Spec 02, seul le point d'attache existe.
- **RGP-11** — Suppression d'un nœud : interdite s'il porte des occurrences terrain (Spec 03), des indicateurs (Spec 05) ou des dépenses (Spec 06) ; sinon soft delete avec confirmation. Un nœud `activite` supprimable uniquement s'il n'a aucune occurrence rattachée.

### Équipe projet
- **RGP-12** — L'**équipe projet** associe des **comptes** (`users`) et/ou des **membres sans compte** (`team_members`, Spec 01) au projet, chacun avec un **rôle dans le projet**. Ce rôle est choisi dans un **référentiel de rôles projet propre à l'organisation** (`project_roles`, org-scopé) : une **liste nationale par défaut** est fournie au seed (chef de projet, membre, appui S&E, appui financier, point focal terrain, coordinateur, animateur…) et **chaque OSC peut l'étendre** avec ses rôles spécifiques (texte libre contrôlé — création à la volée depuis le sélecteur, réutilisable ensuite). Le rôle projet est **distinct du rôle plateforme** (spatie) : il décrit la fonction dans ce projet, pas les permissions.
- **RGP-13** — Un projet a **au moins un chef de projet** (compte). L'affectation d'un compte à l'équipe est une condition de visibilité (RGP-14) mais ne modifie pas ses permissions plateforme.

### Visibilité et accès bailleur
- **RGP-14** — Visibilité intra-organisation : `admin` et `responsable_se` voient tous les projets de l'organisation ; `chef_projet`, `responsable_financier`, `agent_terrain` voient les projets où ils sont **membres de l'équipe** (plus, pour agent terrain, ceux où une activité leur est assignée — Spec 03) ; `consultant` selon affectation et expiration (Spec 01).
- **RGP-15** — **Accès bailleur** (story 2.7) : un `admin` ou un `chef_projet` **partage explicitement** un projet à un compte de rôle `bailleur`. Le partage est à l'initiative de l'organisation, **révocable à tout moment** (révocation immédiate à la requête suivante, cf. RG-10 fraîcheur). Un même bailleur peut recevoir plusieurs projets ; un projet peut être partagé à plusieurs bailleurs.
- **RGP-16** — **Périmètre strict de la vue bailleur** (lecture seule) : identité du projet, période, statut, avancement, activités réalisées (agrégées), indicateurs (réalisé vs cible), **budget synthétique** (par ligne/bailleur, sans pièces justificatives nominatives). **Jamais** : données personnelles de bénéficiaires, commentaires internes, pièces jointes internes, autres projets ou autres bailleurs de l'organisation. Toute **consultation bailleur est journalisée** (RGP-18).

### Transversal
- **RGP-17** — Soft deletes sur projets et nœuds du cadre logique ; jamais de suppression physique d'un projet ayant des données rattachées (préférer clôture/archivage).
- **RGP-18** — Audit (spatie/activitylog, hérité Spec 01) : création/modification/suppression de projet, changements de statut, modifications du cadre logique, affectations d'équipe, **partages et révocations bailleur**, et **accès en consultation bailleur**. Périmètre : admin d'organisation (son organisation), super-admin (plateforme, sans données métier par défaut).

---

## 3. Modèle de données

```
projects            ulid, organization_id FK, code (unique/org), title, description,
                    target_groups (text), start_date, end_date,
                    status (brouillon|valide|en_cours|suspendu|cloture),
                    created_by FK users, timestamps, soft_deletes

project_sectors     pivot project_id × sector_id                (n-n, secteurs Spec 01)

project_zones       ulid, project_id, geo_unit_id nullable, locality_id nullable
                    (exactement un des deux non nul)             (zone d'intervention)

project_donors      ulid, project_id, donor_id, amount_gnf,
                    amount_foreign nullable, foreign_currency nullable   (cofinancement)

logframe_nodes      ulid, project_id, parent_id FK self nullable,
                    type (objectif_general|objectif_specifique|resultat|activite),
                    code, title, description, position, timestamps, soft_deletes
                    — indicateurs attachés en Spec 05 (morph indicatable)

project_roles       ulid, organization_id nullable (null = national/seed), name
                    — référentiel de rôles projet, extensible par l'OSC (RGP-12)

project_members     ulid, project_id, user_id nullable, team_member_id nullable,
                    project_role_id FK project_roles, timestamps
                    (exactement un de user_id / team_member_id non nul)

project_shares      ulid, project_id, user_id FK (compte bailleur), shared_by FK users,
                    shared_at, revoked_at nullable                (accès bailleur, RGP-15)

project_status_changes  ulid, project_id, from_status, to_status, reason nullable,
                    changed_by FK users, created_at              (historique RGP-06)
```

Relations : organization 1-n projects ; project 1-n logframe_nodes (arbre par parent_id) ; project n-n donors (avec montants) ; project n-n sectors ; project 1-n zones ; project 1-n members (comptes + membres) ; project 1-n shares (bailleurs). Toutes les entités portent `organization_id` (direct ou hérité du projet) pour le global scope et les tests d'isolation (RG-02).

**Contraintes clés :** `projects.code` unique par organisation ; `project_members` et `project_zones` : contrainte « exactement un des deux FK ». Numérotation des nœuds proposée côté application, non contrainte en base (modifiable).

---

## 4. Workflows et statuts

**Projet :**
`brouillon` →(validation)→ `validé` →(démarrage)→ `en_cours` →(clôture, motif)→ `clôturé` (terminal).
`validé`/`en_cours` ⇄(suspension motivée / réactivation)→ `suspendu`.
`brouillon` →(suppression)→ soft delete.
`suspendu` et `clôturé` = lecture seule des données rattachées ; réactivation d'un `suspendu` rétablit l'écriture.

**Cadre logique :** édition libre (ajout/déplacement/suppression de nœuds) tant que le projet n'est pas `clôturé` ; en lecture seule dès `suspendu`/`clôturé`.

**Partage bailleur :** `partagé` →(révocation)→ `révoqué` (immédiat) ; ré-partageable.

---

## 5. Écrans (Filament)

**Panel App (tenant) — groupe « Projets » :**
1. *Projets* — liste/table (code, titre, statut badge, période, bailleurs, secteur, avancement) filtrable par statut/secteur/bailleur/année ; visibilité selon RGP-14. Action **Créer** (formulaire une Section pleine largeur + Fieldsets : Identité, Période, Secteurs, Zone, Bailleurs & montants ; labels FR — cf. consigne projet).
2. *Fiche projet* — en-tête (identité, statut + transitions autorisées avec modale motif, historique de statut) et onglets :
   - **Cadre logique** — arbre éditable (objectifs → résultats → activités), ajout/déplacement/numérotation, point d'attache indicateurs (Spec 05).
   - **Équipe** — comptes + membres sans compte, rôle projet ; ajout/retrait.
   - **Bailleurs & financement** — bailleurs et montants (GNF, devise informative).
   - **Zone** — unités géo + localités.
   - **Partages bailleur** — liste des comptes bailleurs, partager/révoquer (RGP-15).
3. *Chronogramme (Gantt)* — vue Frappe Gantt des activités du cadre logique (dates, glisser-déposer, dépendances simples), intégrée dès cette spec (story 2.5).
4. *Portefeuille* — vue directeur (tous projets : statuts, périodes, montants, avancement, consommation), intégrée dès cette spec (story 2.6), accès admin/direction/S&E.

Le **référentiel de rôles projet** (`project_roles`, RGP-12) est géré dans la section « Référentiels » de l'organisation (Spec 01), aux côtés des étiquettes/secteurs/bailleurs ; il est aussi enrichissable à la volée depuis le sélecteur de rôle de l'onglet Équipe.

**Vue bailleur (rôle `bailleur`)** — accès restreint aux seuls projets partagés (RGP-16), lecture seule, sans données personnelles ni commentaires internes ; consultations journalisées.

---

## 6. Matrice de permissions (extrait)

| Action | admin | chef_projet | responsable_se | responsable_financier | agent_terrain | consultant | bailleur |
|---|---|---|---|---|---|---|---|
| Voir projets | tous | équipe | tous | équipe | assignés | selon affectation | partagés |
| Créer / éditer projet | ✅ | ✅ (les siens) | — | — | — | — | — |
| Valider / suspendre / clôturer | ✅ | ✅ (les siens) | — | — | — | — | — |
| Éditer cadre logique | ✅ | ✅ | proposer (S&E) | — | — | — | — |
| Gérer équipe projet | ✅ | ✅ | — | — | — | — | — |
| Bailleurs & montants | ✅ | ✅ | lecture | lecture | — | — | synthétique |
| Partager / révoquer bailleur | ✅ | ✅ | — | — | — | — | — |
| Vue bailleur (lecture seule) | — | — | — | — | — | — | ✅ |

(La granularité fine et les permissions S&E/financier détaillées relèvent des Specs 05/06 ; ce tableau fixe le cadre projet.)

---

## 7. Cas limites et messages

- Code projet déjà utilisé dans l'organisation → refus avec message (suggestion d'un code libre).
- Date de fin antérieure au début → blocage validation.
- Tentative de transition non autorisée (ex. `clôturé → en_cours`) → action masquée/refusée.
- Suspension/clôture sans motif → blocage (motif obligatoire).
- Suppression d'un projet avec activités/indicateurs/dépenses → refus, orienter vers clôture/archivage.
- Suppression d'un nœud de cadre logique portant des occurrences/indicateurs → refus avec décompte.
- Retrait du **dernier chef de projet** d'un projet → blocage (au moins un requis).
- Partage bailleur d'un projet `brouillon` → interdit (partage possible dès `validé`).
- Accès d'un bailleur à un projet révoqué / non partagé → 404 (aucune fuite d'existence).
- Accès bailleur tentant d'atteindre bénéficiaires/commentaires/autre projet → 403 + audit.

---

## 8. Critères de recette

- Un chef de projet crée un projet (code unique, période valide, ≥ 1 bailleur+montant, zone, secteur), le fait passer `brouillon → validé → en_cours`, chaque transition journalisée avec auteur/horodatage.
- Le cadre logique se construit en arbre (objectif → résultat → activité), se réordonne, se renumérote ; un nœud portant des dépendances refuse la suppression.
- L'équipe projet mêle comptes et membres sans compte ; le dernier chef de projet ne peut être retiré.
- La visibilité respecte RGP-14 (tests : un chef ne voit pas un projet d'une autre équipe ; un agent voit ses projets assignés).
- Le partage bailleur expose la vue synthétique et **rien** des données interdites (test dédié : bénéficiaires/commentaires/autres projets inaccessibles) ; la révocation coupe l'accès à la requête suivante ; les consultations sont auditées.
- **Isolation (RG-02)** : aucun projet, nœud, membre, partage ou montant d'une autre organisation n'est atteignable (URL/ULID, recherche, sélecteurs) — suite de tests d'isolation étendue à ce module.
- `suspendu`/`clôturé` : les données rattachées passent en lecture seule ; la réactivation d'un `suspendu` rétablit l'écriture.

---

## 9. Décisions actées et points ouverts

**Actées :**
- Cadre logique = **arbre polymorphe unique** (`logframe_nodes` à types) plutôt que tables séparées : simplicité de rendu et d'attache des indicateurs.
- Le **nœud « activité »** du cadre logique est la définition ; les occurrences terrain (Spec 03) le référencent.
- Devise de travail **GNF** unique ; montants bailleurs en devise = informatifs (pas de conversion).
- Pas de séparation des rôles imposée (petites structures) : l'auteur peut valider (RGP-06).
- Vue bailleur = **périmètre strict en lecture seule**, partage à l'initiative de l'organisation, révocable, audité.

- Rôles projet = **référentiel par organisation** (`project_roles`, national par défaut + extension OSC), sélecteur avec création à la volée — texte libre contrôlé (tranché).
- **Gantt (2.5) et Portefeuille (2.6) inclus dès Spec 02** (tranché). Les dépendances entre activités restent « simples » ; leur mécanique fine sera confirmée avec la Spec 03.
- Numérotation des nœuds : code **proposé automatiquement** à la création, modifiable, **non recalculé** au réordonnancement, avec action « Renuméroter » optionnelle (tranché — RGP-09).

**Points ouverts (à trancher au cadrage) :**
- Dépendances entre activités du Gantt : périmètre exact (fin→début uniquement ?) — à confirmer avec Spec 03.
- Clôture : réversibilité éventuelle (réouverture admin) — hors V1 par défaut.
