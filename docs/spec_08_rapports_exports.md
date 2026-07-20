# SPEC 08 — Rapports et exports

**Version :** 1.0 — Juillet 2026 · **Réf. :** CDC v2.0, Backlog V1 (Epic 8, stories 8.1–8.6)
**Statut :** validé, en cours de développement

---

## 1. Objectif et périmètre

Ce module produit les **livrables exportables** attendus par les bailleurs et le comptable, en consommant les modules précédents : **rapport d'activités** d'une période (Spec 03), **état des indicateurs** d'un cadre de résultats (Spec 05), **rapport financier** d'un projet (Spec 06). Formats **Excel** (maatwebsite) et **PDF** (dompdf).

**Couvre :** stories 8.1, 8.2, 8.3 (Must) en **Excel et PDF**.
**Ne couvre pas en V1 (à réévaluer) :** canevas bailleurs comme modèles d'export (8.4, [S]) ; import/export Excel des données de référence (8.5, [S]) ; **rapport narratif Word** (8.6, [S], nécessite PHPWord — reporté) ; rapport annuel consolidé multi-projets (8.7, [C]).

**Principe de confidentialité :** aucun rapport ni export ne contient de **nominatif** de bénéficiaire (RGSE-09) — seulement identifiants et **agrégats désagrégés**.

---

## 2. Règles de gestion

- **RGR-01** — **Rapport d'activités** (story 8.1) : pour un projet et une **période** (dates de début/fin), liste les **activités réalisées** (date de réalisation, lieu, responsable) avec **participants désagrégés** (sexe/âge, Spec 03), difficultés et mesures correctives. Périodisation sur `realized_at`. Généré en **Excel et PDF**.
- **RGR-02** — **État des indicateurs** (story 8.2) : pour un **cadre de résultats** (Spec 05), tableau **réalisé vs cible** par indicateur et par période, avec taux d'atteinte. Généré en **Excel et PDF**.
- **RGR-03** — **Rapport financier** (story 8.3) : pour un projet, **budget vs engagé vs dépensé vs disponible** par ligne budgétaire + totaux, taux de consommation (Spec 06). Généré en **Excel** (déjà amorcé par l'export budgétaire) **et PDF**.
- **RGR-04** — Les rapports respectent la **visibilité par rôle** : chef de projet (activités de son équipe), responsable S&E (indicateurs), responsable financier (financier), admin (tout). Le **bailleur** n'accède qu'aux agrégats partagés (RGP-16), sans justificatifs ni nominatifs.
- **RGR-05** — Isolation par organisation (RG-02) : un rapport ne porte que sur des données de l'organisation courante. Les exports sont horodatés et nommés de façon parlante (`rapport-activites-{projet}-{periode}.xlsx`).
- **RGR-06** — Les PDF portent l'en-tête **KIDIANI OSC** / nom de l'organisation, la période et la date de génération.

---

## 3. Modèle de données

Aucune nouvelle table : le module **agrège** les données existantes (activités + désagrégations, indicateurs + cibles + valeurs, lignes budgétaires + dépenses). Les générateurs vivent dans `app/Exports` (Excel) et `resources/views/reports` (PDF).

---

## 4. Écrans (Filament)

**Panel App (tenant) — page « Rapports » (groupe Suivi-évaluation ou dédié) :**
1. Choix du **type de rapport** (activités / indicateurs / financier).
2. Sélection du **projet** (ou cadre de résultats pour les indicateurs) et de la **période** (pour le rapport d'activités).
3. Boutons **Excel** et **PDF** (le financier propose aussi PDF en plus de l'export déjà présent sur le suivi budgétaire).

Chaque écran métier existant conserve ses exports contextuels (ex. bouton Excel sur le suivi budgétaire, Spec 06).

---

## 5. Matrice de permissions (extrait)

| Rapport | admin | chef_projet | responsable_se | responsable_financier | bailleur |
|---|---|---|---|---|---|
| Activités (période) | ✅ | ✅ (équipe) | ✅ | — | agrégat partagé |
| Indicateurs (cadre) | ✅ | lecture | ✅ | lecture | agrégat partagé |
| Financier (projet) | ✅ | lecture | — | ✅ | budget synthétique |

---

## 6. Cas limites et messages

- Période sans activité réalisée → rapport avec en-têtes et « Aucune activité réalisée sur la période ».
- Cadre de résultats sans indicateur → état vide avec en-têtes.
- Projet sans ligne budgétaire → rapport financier vide avec totaux à zéro.
- Aucun nominatif de bénéficiaire dans quelque rapport que ce soit (garde-fou).

---

## 7. Critères de recette

- Un chef de projet génère le rapport d'activités d'un trimestre en Excel et PDF : activités réalisées, participants désagrégés, difficultés.
- Un responsable S&E génère l'état des indicateurs d'un cadre en Excel et PDF : réalisé vs cible, taux d'atteinte.
- Un responsable financier génère le rapport financier d'un projet en Excel et PDF : budget vs dépensé par ligne.
- **Isolation / confidentialité** : aucun rapport ne fuit de données d'une autre organisation ni de nominatif de bénéficiaire.

---

## 8. Décisions actées

- **8.1, 8.2, 8.3 en Excel + PDF** dès Spec 08.
- Word narratif (8.6), canevas bailleurs (8.4), import/export de référence (8.5), consolidé annuel (8.7) : **hors V1** (Word nécessite PHPWord — à installer plus tard).
- Réutilisation des agrégats existants (aucune nouvelle table) ; PDF via dompdf, Excel via maatwebsite ; en-tête KIDIANI OSC.
- Aucun nominatif dans les rapports (RGSE-09).
