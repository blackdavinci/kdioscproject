# SPEC 05 — Suivi-évaluation, indicateurs et bénéficiaires

**Version :** 1.0 — Juillet 2026 · **Réf. :** CDC v2.0, Backlog V1 (Epic 5, stories 5.1–5.6)
**Statut :** à valider avant développement

---

## 1. Objectif et périmètre

Ce module est le cœur du suivi-évaluation : **définition d'indicateurs** rattachés au cadre logique, **saisie de valeurs réalisées** périodisées et désagrégées avec **moyen de vérification**, **tableau réalisé vs cible** avec taux d'atteinte et **graphiques**, **cadres de résultats multi-bailleurs** (mapping n-n), et **registre des bénéficiaires** (comptage unique vs participations, minimisation des données personnelles). Il s'appuie sur la Spec 02 (projet, cadre logique — point d'attache des indicateurs préparé en RGP-10), la Spec 03 (activités réalisées, désagrégations, `realized_at` comme pivot de périodisation) et la Spec 01 (géographie/localités).

**Couvre :** stories 5.1 à 5.4 (Must) et 5.5 (registre des bénéficiaires).
**Ne couvre pas :** l'**import Kobo** comme source de valeurs (Spec 07 — un moyen de vérification « soumission Kobo » est prévu ici comme point d'attache) ; les **alertes de seuil** (5.6, [C]) — hors V1 ; théorie du changement graphique, enquêtes maison, analyses statistiques avancées (5.7, V2).

**Frontière avec la Spec 03 :** une valeur d'indicateur est **périodisée sur `realized_at`** (date de terrain). Une activité réalisée peut **alimenter** un indicateur (ses désagrégations sexe/âge nourrissent le réalisé), mais la valeur reste saisie/validée par le responsable S&E — l'agrégation automatique activités→indicateur est un confort optionnel (cf. points ouverts), pas une contrainte.

---

## 2. Règles de gestion

### Indicateurs
- **RGSE-01** — Un **indicateur** appartient à une organisation et est **rattaché à un nœud du cadre logique** (objectif / résultat / activité, RGP-10) — c'est son « niveau ». Champs : code, libellé, **unité** (nombre, %, ratio…), **sens** (croissant = « plus c'est mieux », ou décroissant), **valeur de référence** (baseline) + date de référence, **axes de désagrégation activés** parmi : sexe, tranche d'âge (mêmes bornes que Spec 03), **localité** (référentiel géo).
- **RGSE-02** — **Cibles périodisées** : un indicateur porte des cibles par **période** (RGSE-05). Chaque cible = une valeur attendue pour une période donnée. Les cibles sont éditées par le responsable S&E.
- **RGSE-03** — Un indicateur peut être **rattaché à plusieurs cadres de résultats** (RGSE-08) : le même indicateur alimente le cadre d'un bailleur A et celui d'un bailleur B (mapping n-n), sans duplication de la saisie.

### Valeurs réalisées
- **RGSE-04** — **Valeur réalisée** : le responsable S&E saisit, pour un indicateur et une **période**, la **valeur totale** et, si des axes sont activés, la **répartition désagrégée** (sexe / âge / localité). Cohérence : la somme d'un axe activé égale le total (**alerte non bloquante par défaut**, blocage si l'organisation impose `enforce_disaggregation` — même réglage que Spec 03, RGA-05b).
- **RGSE-05** — **Périodisation** : chaque indicateur a un **type de période** (mensuel / trimestriel / annuel). Cibles et valeurs sont rattachées à une période identifiée (libellé + dates de début/fin, ex. « 2026-T1 »). La périodisation aval s'appuie sur `realized_at` lorsque des activités alimentent la valeur (Spec 03).
- **RGSE-06** — **Moyen de vérification** : chaque valeur peut porter une pièce justificative — document/photo (medialibrary) et/ou une **référence de soumission Kobo** (champ texte préparé pour la Spec 07). Interne : jamais exposé au bailleur (seul l'agrégat l'est, RGP-16).

### Réalisé vs cible et cadres de résultats
- **RGSE-07** — **Tableau réalisé vs cible** : par indicateur et par période, affichage de la cible, du réalisé, du **taux d'atteinte** (réalisé/cible, borné et coloré) et de **graphiques** (courbes/barres réalisé vs cible dans le temps — plugin apex-charts). Filtrable par projet, cadre de résultats, période.
- **RGSE-08** — **Cadres de résultats** (story 5.4) : un cadre appartient à un projet et, en général, à **un bailleur** (nullable pour un cadre « organisation »). Il regroupe un sous-ensemble d'indicateurs du projet (mapping n-n). Un projet peut avoir plusieurs cadres (un par bailleur) ; un indicateur peut figurer dans plusieurs cadres.

### Registre des bénéficiaires (story 5.5)
- **RGSE-09** — **Bénéficiaire** : entité d'organisation avec **identifiant unique par organisation** (code interne), **données minimales** : sexe, tranche d'âge ou année de naissance, **localité** (référentiel géo). Les **champs nominatifs** (nom, contact) sont **chiffrés au niveau applicatif** (`encrypted` casts) et **n'apparaissent jamais** dans les rapports ni les exports — seuls l'**identifiant abstrait** et les **agrégats désagrégés** y figurent (loi L/2016/037, minimisation).
- **RGSE-10** — **Détection de doublons** à la saisie : à la création d'un bénéficiaire, la plateforme signale les **doublons probables** (rapprochement sur des critères non nominatifs et/ou nominatifs hachés) pour éviter le double comptage — signalement non bloquant, décision laissée à l'opérateur.
- **RGSE-11** — **Comptage unique vs participations** : un bénéficiaire peut participer à plusieurs activités (relation n-n bénéficiaire ↔ activité). Les rapports distinguent **bénéficiaires uniques** (dénombrement distinct) et **participations** (somme des présences). Aucun nominatif dans ces comptages.
- **RGSE-12** — Le **niveau exact de pseudonymisation** (chiffrement de tous les nominatifs vs hachage pour le seul rapprochement) est arbitré au cadrage avec la validation juridique de l'hébergement ; le modèle prévoit le chiffrement dès maintenant.

### Transversal
- **RGSE-13** — Isolation par organisation (RG-02) sur toutes les entités (indicateurs, cibles, valeurs, désagrégations, cadres, bénéficiaires, participations) ; global scope + tests d'isolation. Les nominatifs chiffrés ne sont jamais journalisés en clair (audit `logExcept`).
- **RGSE-14** — Soft deletes ; audit (activitylog) sur indicateurs, cibles, valeurs, cadres et bénéficiaires (sans exposer les nominatifs).

---

## 3. Modèle de données

```
indicators              ulid, organization_id, project_id, logframe_node_id FK,
                        code, label, unit, direction (croissant|decroissant),
                        baseline_value nullable, baseline_date nullable,
                        period_type (mensuel|trimestriel|annuel),
                        disaggregations (jsonb : {sex:bool, age:bool, locality:bool}),
                        timestamps, soft_deletes

indicator_targets       ulid, organization_id, indicator_id FK,
                        period_label, period_start, period_end, target_value

indicator_values        ulid, organization_id, indicator_id FK,
                        period_label, period_start, period_end, value,
                        source (manuelle|kobo), kobo_reference nullable,
                        recorded_by FK users, recorded_at,
                        (moyen de vérification : medialibrary « verification »)
                        timestamps, soft_deletes

indicator_value_disaggregations  ulid, organization_id, indicator_value_id FK,
                        dimension (sex|age|locality), key, count

result_frameworks       ulid, organization_id, project_id FK, donor_id nullable FK, name
result_framework_indicator  pivot framework_id × indicator_id           (mapping n-n, RGSE-08)

beneficiaries           ulid, organization_id, code (unique/org),
                        sex nullable, age_bracket nullable, birth_year nullable,
                        locality_id nullable FK, geo_unit_id nullable FK,
                        full_name (ENCRYPTED), contact (ENCRYPTED),
                        name_fingerprint (hash pour rapprochement), notes nullable,
                        timestamps, soft_deletes

beneficiary_activity    pivot beneficiary_id × activity_id                (participations, RGSE-11)
```

Relations : logframe_node 1-n indicators ; indicator 1-n targets, 1-n values, n-n frameworks ; indicator_value 1-n disaggregations, n médias (vérification) ; project 1-n frameworks ; framework 0..1 donor ; beneficiary n-n activities. Toutes les entités portent `organization_id`. Les colonnes `full_name`/`contact` utilisent des `encrypted` casts ; `name_fingerprint` = hash à sel d'organisation, jamais réversible, pour la détection de doublons (RGSE-10).

---

## 4. Workflows et périodisation

**Périodisation :** l'indicateur fixe un `period_type` ; cibles et valeurs sont rangées par période (`period_label` + bornes). Le tableau réalisé vs cible aligne cible et réalisé période par période et calcule le taux d'atteinte.

**Alimentation par les activités (optionnelle) :** une valeur peut être saisie manuellement ou pré-remplie à partir des activités réalisées de la période (agrégation des participants désagrégés), puis validée par le responsable S&E. La date de rattachement d'une activité à une période est `realized_at` (Spec 03).

**Bénéficiaires :** `créé` (avec détection de doublon) → participations enregistrées au fil des activités ; comptages uniques/participations dérivés à la lecture.

---

## 5. Écrans (Filament)

**Panel App (tenant) — groupe « Suivi-évaluation » :**
1. *Indicateurs* — table (code, libellé, niveau du cadre logique, unité, baseline, type de période) ; formulaire (Section pleine largeur + Fieldsets : Rattachement au cadre logique, Définition unité/sens/baseline, Périodicité, Axes de désagrégation ; labels FR). Onglets : Cibles (par période) et Valeurs.
2. *Saisie de valeur* — période, valeur totale, désagrégations (sexe/âge/localité selon axes activés, contrôle de cohérence), moyen de vérification (fichier + référence Kobo).
3. *Tableau réalisé vs cible* — par indicateur/période : cible, réalisé, taux d'atteinte, **graphique** réalisé vs cible (apex-charts) ; filtres projet / cadre de résultats / période.
4. *Cadres de résultats* — par projet : cadres (un par bailleur), sélection des indicateurs rattachés (n-n).
5. *Bénéficiaires* — registre (identifiant, sexe, âge/localité) ; création avec **détection de doublons** ; les nominatifs sont saisissables mais **jamais** affichés dans les listes/exports ; vue « comptage unique vs participations ».

Accès principal : **responsable S&E** et **admin/direction** (lecture) ; le bailleur ne voit que les agrégats réalisé vs cible via la vue de partage (RGP-16), sans nominatif ni moyen de vérification interne.

---

## 6. Matrice de permissions (extrait)

| Action | admin | chef_projet | responsable_se | responsable_financier | agent_terrain | consultant | bailleur |
|---|---|---|---|---|---|---|---|
| Définir indicateurs / cibles | ✅ | proposer | ✅ | — | — | — | — |
| Saisir des valeurs réalisées | ✅ | — | ✅ | — | — | selon affectation | — |
| Gérer cadres de résultats | ✅ | ✅ | ✅ | — | — | — | — |
| Voir réalisé vs cible | ✅ | équipe | ✅ | équipe | — | affectation | agrégats partagés |
| Registre des bénéficiaires | ✅ | — | ✅ | — | saisie limitée | — | — |
| Voir nominatifs bénéficiaires | ✅ | — | ✅ | — | — | — | ❌ jamais |

---

## 7. Cas limites et messages

- Désagrégation incohérente (somme axe ≠ total) → alerte (ou blocage si `enforce_disaggregation`).
- Valeur sans cible correspondante → autorisée ; taux d'atteinte affiché « — ».
- Taux d'atteinte pour un indicateur décroissant → calcul inversé (réalisé ≤ cible = atteint).
- Doublon probable de bénéficiaire → signalé, non bloquant ; l'opérateur confirme ou fusionne.
- Tentative d'affichage d'un nominatif dans un rapport/export → **impossible** (les exports ne contiennent que l'identifiant + agrégats).
- Indicateur rattaché à plusieurs cadres → une seule saisie de valeur alimente tous les cadres.
- Suppression d'un indicateur portant des valeurs → refus (soft delete/archivage).

---

## 8. Critères de recette

- Un responsable S&E définit un indicateur rattaché à un résultat (unité, sens, baseline, axes sexe+localité), fixe des cibles trimestrielles, saisit une valeur désagrégée cohérente avec un moyen de vérification.
- Le tableau réalisé vs cible affiche cible/réalisé/taux d'atteinte par période, avec un graphique.
- Le même indicateur est rattaché à deux cadres de résultats (deux bailleurs) sans double saisie.
- Un bénéficiaire est créé avec détection de doublon ; ses nominatifs sont chiffrés en base et **absents** des listes/exports ; les comptages distinguent uniques et participations.
- **Isolation (RG-02) et confidentialité :** aucun indicateur, valeur, cadre ou bénéficiaire d'une autre organisation n'est atteignable ; aucun nominatif n'apparaît en clair dans l'audit, les rapports ou les exports ; le bailleur ne voit que des agrégats.

---

## 9. Décisions actées et points ouverts

**Actées (par cohérence avec l'existant) :**
- Indicateurs rattachés au **cadre logique** (point d'attache préparé en Spec 02) ; désagrégations sexe/âge **mêmes bornes que Spec 03**, plus localité.
- Cohérence des désagrégations : **alerte non bloquante par défaut**, blocage via le réglage d'organisation `enforce_disaggregation` (réutilisé de Spec 03).
- Nominatifs bénéficiaires **chiffrés** (`encrypted` casts) + hash de rapprochement ; **jamais** dans rapports/exports/audit.
- Périodisation aval sur `realized_at` (Spec 03) ; graphiques via **apex-charts**.

- **Cadres de résultats multi-bailleurs (5.4, Must)** : inclus dès Spec 05.
- **Registre des bénéficiaires (5.5)** : inclus en V1 dès Spec 05, avec chiffrement des nominatifs.
- **Alimentation activités→indicateur** : **saisie manuelle** (le responsable S&E saisit/valide) en V1 ; le pré-remplissage automatique depuis les activités réalisées est un confort reporté.
- **Type de période** : **au choix par indicateur** (mensuel/trimestriel/annuel).
- **Alertes de seuil (5.6, [C])** : **hors V1**.
- **Niveau de pseudonymisation** (RGSE-12) : **chiffrer tous les nominatifs** (`encrypted` casts) + hash de rapprochement, dès maintenant ; à revalider au cadrage juridique.
