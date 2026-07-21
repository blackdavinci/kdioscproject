# SPEC 09 — Tableaux de bord et notifications

**Version :** 1.0 — Juillet 2026 · **Réf. :** CDC v2.0, Backlog V1 (Epic 9, stories 9.1–9.5)
**Statut :** validé, en cours de développement

---

## 1. Objectif et périmètre

Ce module donne la **vue d'ensemble** et le **fil de notifications** : tableau de bord adapté au rôle (direction / chef de projet), synthèse des projets, de la consommation budgétaire, des indicateurs et des alertes, dernières activités ; **centre de notifications in-app** (cloche, déjà en place depuis la Spec 04) complété par les **alertes budgétaires** proactives. Consomme les Specs 02/03/05/06.

**Couvre :** 9.1 (dashboard direction), 9.2 (dashboard chef de projet), 9.3 (centre de notifications in-app + e-mail : mentions, assignations, échéances, alertes budgétaires).
**Ne couvre pas en V1 :** rappels SMS (9.4, [C], hors V1) ; tableau de bord S&E cartographique dédié (9.5, [C] — la carte des interventions existe déjà en Spec 03) ; les échecs d'import Kobo (module Kobo reporté en V2).

---

## 2. Règles de gestion

- **RGD-01** — **Tableau de bord** unique, dont le contenu s'**adapte au rôle et à la visibilité** : la direction (admin, responsable S&E) voit l'ensemble de l'organisation ; le chef de projet voit **ses projets** (équipe) ; les autres rôles voient au minimum ce qui les concerne. Aucune donnée d'une autre organisation (RG-02).
- **RGD-02** — **Synthèse (stats)** : nombre de **projets en cours**, **consommation budgétaire globale** (dépensé / budget des projets visibles, en %), **tâches en retard**, **alertes budgétaires** (lignes ayant atteint leur seuil).
- **RGD-03** — **Dernières activités** : liste des activités réalisées récentes (visibles), avec projet, date de réalisation et participants.
- **RGD-04** — **Indicateurs clés** : rappel synthétique de l'atteinte moyenne des indicateurs des projets visibles (facultatif V1, sinon lien vers Réalisé vs cible).
- **RGD-05** — **Centre de notifications** in-app (cloche Filament, table `notifications`) : reçoit les **mentions** (RGT-09), les **assignations** de tâche, les **rappels d'échéance** (RGT-13), le **récap des retards** (RGT-14) et les **alertes budgétaires** (RGB-07). L'e-mail double l'in-app pour les événements adressés à une personne (mentions, assignations, échéances) via la config mail centralisée (Spec 01).
- **RGD-06** — **Alerte budgétaire proactive** (complète RGB-07) : une commande planifiée quotidienne notifie le **responsable financier** et l'**admin** lorsqu'une ligne budgétaire atteint son seuil ou dépasse le budget (in-app + e-mail), sans double alerte tant que l'état ne change pas de palier.
- **RGD-07** — **Assignation de tâche** : lorsqu'une tâche est assignée à un compte, l'assigné reçoit une notification in-app + e-mail (RGD-05).

---

## 3. Modèle de données

Aucune nouvelle table métier. Les notifications utilisent la table `notifications` (déjà présente, `data` en jsonb). Le suivi « palier d'alerte déjà notifié » d'une ligne budgétaire est porté par une colonne légère `alert_notified_at` (ou équivalent) sur `budget_lines` pour éviter les doublons quotidiens.

---

## 4. Écrans (Filament)

**Panel App (tenant) — Tableau de bord (page d'accueil) :**
1. **Widgets de synthèse** (stats) : projets en cours, consommation budgétaire, tâches en retard, alertes budgétaires — chiffres scopés au rôle.
2. **Dernières activités** (table widget).
3. **Alertes budgétaires** (lignes au-dessus du seuil) — table ou stat cliquable vers le suivi budgétaire.

**Cloche de notifications** (barre supérieure, déjà active) : liste déroulante des notifications, marquage lu/non lu.

---

## 5. Matrice de permissions (extrait)

| Élément | admin | chef_projet | responsable_se | responsable_financier | bailleur |
|---|---|---|---|---|---|
| Dashboard (portée) | organisation | ses projets | organisation | organisation (budget) | — (vue partagée) |
| Alertes budgétaires | ✅ | lecture | — | ✅ | — |
| Notifications | ✅ | ✅ | ✅ | ✅ | limitées |

---

## 6. Cas limites et messages

- Organisation sans projet → dashboard avec zéros et invitations à créer.
- Aucune ligne budgétaire → consommation « — », zéro alerte.
- Notification adressée à une fiche membre sans compte → pas d'e-mail (pas d'adresse), pas d'in-app.
- Pas de double alerte budgétaire tant que le palier de la ligne n'a pas changé.

---

## 7. Critères de recette

- La direction voit un dashboard synthétique de toute l'organisation ; le chef de projet un dashboard limité à ses projets.
- Une ligne budgétaire franchissant son seuil génère une alerte in-app + e-mail au responsable financier (une seule fois par franchissement).
- Une assignation de tâche notifie l'assigné (in-app + e-mail).
- **Isolation (RG-02)** : aucun chiffre ni notification d'une autre organisation.

---

## 8. Décisions actées

- Un **tableau de bord unique** dont le contenu s'adapte au rôle/visibilité (pas de page distincte par rôle en V1).
- Centre de notifications = **cloche Filament existante** (Spec 04) enrichie des alertes budgétaires et assignations.
- **Alerte budgétaire proactive** via commande planifiée + garde anti-doublon (`budget_lines.alert_notified_at`).
- **SMS (9.4) et dashboard S&E cartographique dédié (9.5) hors V1** ; échecs Kobo → V2.
