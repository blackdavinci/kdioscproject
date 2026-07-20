# SPEC 06 — Suivi budgétaire

**Version :** 1.0 — Juillet 2026 · **Réf. :** CDC v2.0, Backlog V1 (Epic 6, stories 6.1–6.6)
**Statut :** validé, en cours de développement

---

## 1. Objectif et périmètre

Ce module suit l'exécution financière des projets : **budget en lignes** (rubriques paramétrables) avec **répartition par bailleur** (cofinancement), **dépenses** rattachées à une ligne et à une activité avec justificatif, tableau **budget vs dépensé vs disponible** avec taux de consommation, **alertes de seuil**, distinction **engagements / dépenses réalisées**, et **export Excel**. Il s'appuie sur la Spec 02 (projet, bailleurs) et la Spec 03 (activités).

**Couvre :** stories 6.1 à 6.6 (dont engagements 6.5 et export Excel 6.6, retenus en V1).
**Ne couvre pas (6.7, W) :** comptabilité générale, rapprochements bancaires, paiements, multi-devises comptable. **Devise unique de travail : le GNF** ; les montants bailleurs en devise d'origine restent informatifs (Spec 02, RGP-03).

---

## 2. Règles de gestion

- **RGB-01** — **Rubriques budgétaires** : référentiel de catégories (`budget_categories`) national par défaut (fourni au seed : Personnel, Équipements, Fonctionnement, Activités, Déplacements, Formation, Suivi-évaluation, Frais administratifs…), **extensible par l'organisation** (même modèle national+propre que les secteurs, Spec 01).
- **RGB-02** — **Ligne budgétaire** (`budget_lines`) : appartient à un projet, référence une rubrique, porte un **libellé**, un **montant budgété en GNF** et un **seuil d'alerte** (% de consommation, défaut 80). Un projet a autant de lignes que nécessaire.
- **RGB-03** — **Répartition par bailleur** (cofinancement) : une ligne peut être répartie entre plusieurs bailleurs (`budget_line_allocations` : ligne × bailleur × montant GNF). La somme des répartitions doit être cohérente avec le montant de la ligne (**alerte non bloquante** si écart). Sans répartition, la ligne est financée globalement.
- **RGB-04** — **Dépense** (`expenses`) : rattachée à une **ligne budgétaire** et, facultativement, à une **activité** (Spec 03). Champs : **type** (engagement / réalisée), libellé, **montant GNF**, **date** de la dépense, justificatif scanné (medialibrary). Saisie par le responsable financier.
- **RGB-05** — **Engagements vs dépenses réalisées** (story 6.5) : le type `engagement` = dépense prévue non encore payée ; `realisee` = dépense effective. Le suivi distingue les deux : **dépensé** = somme des réalisées, **engagé** = somme des engagements, **disponible** = budget − dépensé − engagé.
- **RGB-06** — **Budget vs dépensé vs disponible** (story 6.3) : par **ligne**, par **projet** et par **bailleur** (via les répartitions), avec **taux de consommation** (dépensé / budget) coloré. Agrégations calculées, jamais stockées.
- **RGB-07** — **Alertes de seuil** (story 6.4) : quand le taux de consommation d'une ligne atteint son **seuil** (défaut 80 %) ou la **dépasse** (> 100 %), une alerte est signalée (in-app au responsable financier et à l'admin, et visuellement dans le tableau). Paramétrable par ligne.
- **RGB-08** — **Export Excel** (story 6.6) : l'état budgétaire d'un projet (lignes, budget, engagé, dépensé, disponible, taux) est exportable au format **Excel** (maatwebsite/excel) pour le comptable et le rapport financier bailleur. Aucun nominatif.
- **RGB-09** — Isolation par organisation (RG-02) ; soft deletes ; audit (activitylog) sur lignes, répartitions et dépenses. Justificatifs internes (jamais exposés au bailleur ; celui-ci ne voit qu'un **budget synthétique**, RGP-16).

---

## 3. Modèle de données

```
budget_categories       ulid, organization_id nullable (null = national), name
                        (référentiel national + propre, extensible — comme les secteurs)

budget_lines            ulid, organization_id, project_id FK, budget_category_id FK,
                        label, amount_gnf, threshold_percent (default 80),
                        timestamps, soft_deletes

budget_line_allocations ulid, organization_id, budget_line_id FK, donor_id FK, amount_gnf
                        (répartition d'une ligne entre bailleurs — cofinancement)

expenses                ulid, organization_id, project_id FK, budget_line_id FK,
                        activity_id nullable FK, kind (engagement|realisee),
                        label, amount_gnf, spent_on (date),
                        recorded_by FK users, timestamps, soft_deletes
                        (justificatif : medialibrary « justificatif »)
```

Relations : project 1-n budget_lines ; budget_category 1-n budget_lines ; budget_line 1-n allocations, 1-n expenses ; expense 0..1 activity. Toutes les entités portent `organization_id`. Montants en **GNF** (entiers), jamais convertis.

---

## 4. Workflows et calculs

**Consommation d'une ligne :** `budget = amount_gnf` ; `dépensé = Σ expenses(realisee)` ; `engagé = Σ expenses(engagement)` ; `disponible = budget − dépensé − engagé` ; `taux = dépensé / budget`.

**Alerte :** `taux ≥ seuil` → alerte « seuil atteint » ; `dépensé > budget` → alerte « dépassement ». Signalée in-app et colorée dans le tableau (vert < seuil, orange ≥ seuil, rouge > 100 %).

**Par bailleur :** la consommation attribuable à un bailleur se lit via les répartitions de lignes (proportionnelle) — vue synthétique.

---

## 5. Écrans (Filament)

**Panel App (tenant) — groupe « Budget » :**
1. *Lignes budgétaires* — par projet : table (rubrique, libellé, budget, engagé, dépensé, disponible, taux coloré) ; formulaire (Section pleine largeur + Fieldsets : Rattachement projet/rubrique, Montant & seuil, Répartition bailleurs ; labels FR).
2. *Dépenses* — ressource liste/table (date, libellé, ligne, activité, type, montant, justificatif) ; création rattachée à une ligne (+ activité optionnelle).
3. *Suivi budgétaire (tableau)* — par projet : budget vs engagé vs dépensé vs disponible par ligne + totaux, taux de consommation coloré, **bouton Export Excel**.
4. *Référentiel* — rubriques budgétaires (national + propres) dans la section Référentiels de l'organisation.

Accès : **responsable financier** et **admin** (édition), **direction / chef de projet** (lecture). Le **bailleur** ne voit qu'un budget synthétique via la vue de partage (RGP-16), sans justificatifs.

---

## 6. Matrice de permissions (extrait)

| Action | admin | chef_projet | responsable_se | responsable_financier | agent_terrain | consultant | bailleur |
|---|---|---|---|---|---|---|---|
| Gérer lignes budgétaires | ✅ | lecture | — | ✅ | — | — | — |
| Saisir des dépenses/engagements | ✅ | — | — | ✅ | — | selon affectation | — |
| Voir budget vs dépensé | ✅ | équipe | lecture | ✅ | — | affectation | synthétique |
| Exporter l'état budgétaire | ✅ | — | — | ✅ | — | — | — |
| Voir justificatifs | ✅ | — | — | ✅ | — | — | ❌ |

---

## 7. Cas limites et messages

- Répartition bailleurs ≠ montant de la ligne → alerte non bloquante indiquant l'écart.
- Dépense dépassant le disponible → autorisée mais **alerte de dépassement** ; taux > 100 % en rouge.
- Dépense sans activité → autorisée (dépense de projet non rattachée à une activité).
- Suppression d'une ligne portant des dépenses → refus (soft delete/archivage).
- Seuil d'alerte hors 1–100 → refusé.
- Export d'un projet sans ligne → fichier vide avec en-têtes.

---

## 8. Critères de recette

- Un responsable financier crée des lignes budgétaires (rubriques), réparties entre deux bailleurs (cofinancement cohérent), saisit des engagements et des dépenses réalisées rattachés à une ligne et une activité, avec justificatif.
- Le tableau affiche par ligne budget / engagé / dépensé / disponible / taux ; une ligne atteignant 80 % déclenche une alerte, un dépassement (> 100 %) une alerte rouge.
- L'état budgétaire s'exporte en Excel (lignes + totaux), sans nominatif.
- **Isolation (RG-02)** : aucune ligne, dépense ou répartition d'une autre organisation n'est atteignable ; le bailleur ne voit qu'un budget synthétique, sans justificatif.

---

## 9. Décisions actées

- **Rubriques** = référentiel national + propre (extensible), comme les secteurs (Spec 01).
- **Cofinancement** par répartition ligne × bailleur ; cohérence en alerte non bloquante.
- **Engagements (6.5) et export Excel (6.6) retenus en V1.**
- **Alertes de seuil (6.4)** : in-app + coloration du tableau, seuil paramétrable par ligne (défaut 80 %).
- **GNF unique** ; pas de conversion ; montants bailleurs en devise = informatifs.
- Justificatifs internes ; le bailleur ne voit que l'agrégat (RGP-16).

**Point ouvert (mineur) :** attribution fine de la consommation par bailleur (proportionnelle aux répartitions) — vue synthétique en V1, granularité exacte à affiner au besoin.
