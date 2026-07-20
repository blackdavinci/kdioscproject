# SPEC 04 — Tâches et collaboration

**Version :** 1.0 — Juillet 2026 · **Réf. :** CDC v2.0, Backlog V1 (Epic 4, stories 4.1–4.9)
**Statut :** à valider avant développement

---

## 1. Objectif et périmètre

Ce module apporte la couche collaborative quotidienne : **tâches** (rattachées à un projet, une activité, ou internes hors projet), **kanban** avec glisser-déposer, vue **« mes tâches »**, **commentaires** avec **mentions @**, **étiquettes**, **pièces jointes** et **tâches récurrentes**. Il s'appuie sur la Spec 01 (comptes, membres, étiquettes, config mail), la Spec 02 (projets, équipe) et la Spec 03 (activités).

**Couvre :** stories 4.1 à 4.8 (dont récurrence 4.6, pièces jointes 4.7 et récap hebdo 4.8, retenues en V1).
**Ne couvre pas :** le **SMS sur mention** (4.9, [C]) — hors V1 (coût, à réévaluer) ; le centre de notifications complet et les récapitulatifs planifiés riches (Spec 09) — la Spec 04 pose les notifications de mention (in-app + e-mail) et le rappel d'échéance dont elle a besoin ; les sous-tâches, dépendances et estimation en points (4.10, V2).

**Frontière notifications :** les notifications in-app utilisent le système de notifications Filament/Laravel (table `notifications`, déjà présente) ; l'e-mail réutilise la config mail centralisée (Spec 01). Le **centre de notifications unifié** (cloche, préférences, agrégation) est finalisé en Spec 09.

---

## 2. Règles de gestion

### Tâches
- **RGT-01** — Une **tâche** appartient à une organisation (isolation RG-02). Elle peut être **rattachée** à un projet et/ou à une activité, **ou interne** (hors projet). Champs : titre, description, **assigné** (compte ou membre sans compte), **échéance** (date, optionnelle), **priorité** (basse / normale / haute / urgente), **statut** (à faire / en cours / bloqué / terminé), **étiquettes** (référentiel fermé Spec 01), position (ordre kanban).
- **RGT-02** — L'**assigné** est un compte ou une fiche membre de l'organisation. Pour une tâche de projet, l'assignation est proposée en priorité parmi l'équipe projet ; une tâche interne peut être assignée à tout compte/membre de l'organisation.
- **RGT-03** — Statuts kanban : `a_faire` → `en_cours` → `termine`, avec `bloque` atteignable depuis `a_faire`/`en_cours`. Le passage à `termine` horodate la clôture. Toute transition est libre (pas de workflow contraint), tracée en audit.
- **RGT-04** — **Tâche interne** (RGT hors projet, story 4.5) : `project_id` et `activity_id` nuls. Visible dans « mes tâches » et le kanban avec un filtre « hors projet ». Sert l'administratif, le régalien, la logistique.
- **RGT-05** — Visibilité : une tâche de projet suit le périmètre projet (RGP-14) ; une tâche interne est visible de son auteur, de son assigné et des admins. Chacun voit au minimum **ses** tâches (assignées à lui) toutes sources confondues.

### Kanban et « mes tâches »
- **RGT-06** — **Kanban** (story 4.2) : colonnes = statuts (à faire / en cours / bloqué / terminé), **glisser-déposer** d'une carte pour changer de statut et réordonner, **filtres** par projet, assigné, étiquette et « hors projet ». Le déplacement persiste statut + position.
- **RGT-07** — **« Mes tâches »** (story 4.3) : liste de toutes les tâches assignées à l'utilisateur courant, toutes sources confondues (projet, activité, interne), **triées par échéance** (les plus proches d'abord, dépassées en tête et signalées).

### Commentaires et mentions
- **RGT-08** — **Commentaires** (story 4.4) : polymorphes, attachables à une **tâche** comme à une **activité** (Spec 03). Champs : auteur (compte), corps, horodatage. **Non supprimables silencieusement** : une suppression est un soft delete tracé en audit ; une **édition** est tracée (`edited_at`, l'historique reste consultable en audit).
- **RGT-09** — **Mentions @** : l'autocomplétion des mentions est **strictement limitée aux comptes de l'organisation** (test d'isolation dédié, RG-02). Une personne mentionnée reçoit une **notification in-app + e-mail**. Les mentions d'un commentaire sont enregistrées (qui a été notifié).
- **RGT-10** — Notifications : la notification in-app pointe vers la tâche/activité commentée ; l'e-mail réutilise l'expéditeur configuré de l'organisation (Spec 01, modèle centralisé). Pas de fuite inter-organisation (un mentionné est toujours de la même organisation).

### Étiquettes, pièces jointes, récurrence
- **RGT-11** — **Étiquettes** : réutilise le référentiel fermé de l'organisation (Spec 01, RG-18) ; rattachement n-n aux tâches ; filtrables au kanban. Pas de création à la volée par les utilisateurs (gérées par l'admin).
- **RGT-12** — **Pièces jointes** (story 4.7) : documents joints à une tâche via medialibrary (mêmes formats/taille que les activités : JPEG/PNG/PDF, ≤ 10 Mo, optimisation serveur). Internes (jamais exposées au bailleur).
- **RGT-13** — **Tâches récurrentes** (story 4.6) : une tâche peut être récurrente (mensuelle / trimestrielle / annuelle). À la **clôture** d'une occurrence, la suivante est générée automatiquement (nouvelle échéance décalée). **Rappel à J-X** paramétrable (notification in-app + e-mail à l'assigné). Cas d'usage : payer l'abonnement, renouveler l'agrément, déposer le rapport annuel.

### Récapitulatifs et transversal
- **RGT-14** — **Tâches en retard** (story 4.8) : récapitulatif **hebdomadaire** des tâches en retard de l'équipe, notifié au chef de projet (job planifié, in-app + e-mail).
- **RGT-15** — **SMS sur mention** (story 4.9, [C]) : **hors V1** (coût). Réservé : quota mensuel par organisation, décompte visible, blocage à épuisement — à réactiver ultérieurement via le module SMS (Spec 09).
- **RGT-16** — Soft deletes ; audit (spatie/activitylog) sur création/modification/suppression de tâche, changement de statut, commentaire (création/édition/suppression), mention.

---

## 3. Modèle de données

```
tasks               ulid, organization_id, project_id nullable FK, activity_id nullable FK,
                    title, description, assignee_user_id nullable, assignee_team_member_id nullable,
                    due_date nullable, priority (basse|normale|haute|urgente),
                    status (a_faire|en_cours|bloque|termine), position,
                    completed_at nullable,
                    recurrence (aucune|mensuelle|trimestrielle|annuelle),
                    reminder_days_before nullable, recurrence_group_id ulid nullable,
                    created_by FK users, timestamps, soft_deletes

task_tag            pivot task_id × tag_id                         (étiquettes, RGT-11)

comments            ulid, organization_id, commentable_type, commentable_id (morph : task|activity),
                    user_id FK, body, edited_at nullable, timestamps, soft_deletes

comment_mentions    ulid, comment_id FK, user_id FK (mentionné)    (traçe des notifiés, RGT-09)

(pièces jointes)    via medialibrary : collection « pieces_jointes » sur task (RGT-12)
```

Relations : task 0..1 project, 0..1 activity, 0..1 assigné (compte **ou** membre), n étiquettes, n commentaires (morph), n médias ; comment 1 auteur, n mentions. `recurrence_group_id` relie les occurrences d'une tâche récurrente. Toutes les entités portent `organization_id` (global scope + tests d'isolation).

---

## 4. Workflows et statuts

**Tâche :** `à faire` ⇄ `en cours` ⇄ `bloqué` → `terminé` (clôture horodatée). Transitions libres au glisser-déposer ; l'audit trace chaque changement.

**Tâche récurrente :** à la clôture d'une occurrence, génération de la suivante (échéance décalée selon la fréquence) reliée par `recurrence_group_id` ; rappel à J-X avant échéance.

**Commentaire :** `publié` →(édition, tracée `edited_at`)→ `édité` ; suppression = soft delete tracé (jamais silencieux).

---

## 5. Écrans (Filament)

**Panel App (tenant) — groupe « Collaboration » :**
1. *Tableau kanban* — colonnes à faire / en cours / bloqué / terminé, cartes déplaçables (glisser-déposer), filtres projet / assigné / étiquette / hors projet ; création rapide de tâche.
2. *Mes tâches* — liste triée par échéance (retards en tête), toutes sources, filtres statut/priorité.
3. *Tâches* — ressource liste/table classique (titre, projet/activité ou « interne », assigné, échéance, priorité, statut, étiquettes) ; formulaire (Section pleine largeur + Fieldsets : Rattachement, Détails, Assignation & échéance, Étiquettes, Pièces jointes, Récurrence ; labels FR).
4. *Fiche tâche* — détails + **fil de commentaires** avec mentions @ (autocomplétion limitée à l'organisation) et pièces jointes.

**Commentaires sur activité** — le fil de commentaires (RGT-08) est aussi disponible sur la fiche d'activité (Spec 03).

---

## 6. Matrice de permissions (extrait)

| Action | admin | chef_projet | responsable_se | responsable_financier | agent_terrain | consultant | bailleur |
|---|---|---|---|---|---|---|---|
| Créer / éditer une tâche | ✅ | ✅ (équipe) | ✅ (équipe) | ✅ (équipe) | ✅ (les siennes) | selon affectation | — |
| Déplacer au kanban | ✅ | ✅ | ✅ | ✅ | ✅ (assigné) | selon affectation | — |
| Commenter / mentionner | ✅ | ✅ | ✅ | ✅ | ✅ | selon affectation | — |
| Tâche interne (hors projet) | ✅ | ✅ | ✅ | ✅ | ✅ | — | — |
| Voir « mes tâches » | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — |

(Le bailleur n'a pas accès aux tâches ni aux commentaires internes — RGP-16. La visibilité des tâches de projet suit RGP-14.)

---

## 7. Cas limites et messages

- Mention @ d'une personne hors organisation → impossible (autocomplétion scoping ; ignorée si forcée côté serveur).
- Tâche sans échéance → autorisée (n'apparaît pas dans les retards ; classée après les échéances datées dans « mes tâches »).
- Suppression d'un commentaire → soft delete tracé, jamais silencieux ; l'auteur ou un admin peut supprimer, l'audit conserve la trace.
- Édition d'un commentaire → `edited_at` renseigné, marqueur « modifié » affiché.
- Clôture d'une tâche récurrente → génère la suivante ; si la fréquence est « aucune », rien.
- Tâche assignée à une fiche membre sans compte → pas de notification e-mail (pas d'adresse) ; visible dans le kanban et suivie par l'équipe.
- Déplacement kanban d'une tâche d'un projet suspendu/clôturé → lecture seule héritée (RGP-07) : blocage avec message.

---

## 8. Critères de recette

- Un membre crée une tâche de projet (assigné de l'équipe, échéance, priorité, étiquettes) et une tâche **interne** ; les deux apparaissent au kanban (filtre « hors projet » pour l'interne) et dans « mes tâches » triées par échéance.
- Le glisser-déposer d'une carte change son statut et sa position, de façon persistante.
- Un commentaire avec **mention @** notifie la personne (in-app + e-mail) ; l'autocomplétion ne propose que des comptes de l'**organisation** (test d'isolation).
- Un commentaire édité affiche « modifié » (`edited_at`) ; une suppression est tracée en audit (jamais silencieuse).
- Une tâche récurrente clôturée génère l'occurrence suivante ; un rappel à J-X est émis.
- **Isolation (RG-02)** : aucune tâche, commentaire, mention ou pièce jointe d'une autre organisation n'est atteignable ; l'autocomplétion des mentions ne franchit jamais la frontière tenant.

---

## 9. Décisions actées et points ouverts

**Actées :**
- **Commentaires + mentions dès Spec 04 sur tâches ET activités** (fil de commentaires polymorphe).
- **Notifications de mention : in-app + e-mail dès Spec 04**, réutilisant la config mail centralisée (Spec 01).
- **Récurrence (4.6), pièces jointes (4.7) et récap hebdo des retards (4.8) retenues en V1.**
- **Kanban glisser-déposer réel via SortableJS bundlé** (offline-safe, même approche que Leaflet en Spec 03) ; boutons de déplacement en repli d'accessibilité.
- **SMS sur mention (4.9, [C]) hors V1** (coût) — réservé au module SMS (Spec 09).
- Étiquettes = référentiel fermé Spec 01 (pas de création à la volée).
- Pièces jointes internes (jamais dans la vue bailleur), medialibrary comme les activités.
- Tout est isolé par organisation ; suite de tests d'isolation étendue (dont l'autocomplétion des mentions).

**Points ouverts (mineurs, à confirmer au cadrage) :**
- Rappel de tâche récurrente : valeur par défaut de `reminder_days_before` (proposition : 7 jours).
- Récap hebdo des retards : jour/heure d'envoi (proposition : lundi matin).
