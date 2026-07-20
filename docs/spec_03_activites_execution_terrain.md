# SPEC 03 — Activités et exécution terrain

**Version :** 1.0 — Juillet 2026 · **Réf. :** CDC v2.0, Backlog V1 (Epic 3, stories 3.1–3.7)
**Statut :** à valider avant développement

---

## 1. Objectif et périmètre

Ce module fait vivre l'exécution des projets : **planification** d'activités rattachées au cadre logique, **saisie de la réalisation** par les agents de terrain (participants désagrégés, difficultés, mesures correctives), **justificatifs**, **formulaires papier** pour le circuit terrain, **calendrier** et **carte** des interventions. Il s'appuie sur la Spec 02 (projet, cadre logique, équipe, zone) et la Spec 01 (membres, géographie, localités).

**Couvre :** stories 3.1 à 3.7. **Débloque** le chronogramme Gantt (story 2.5, reporté depuis la Spec 02 faute de dates d'activités).
**Ne couvre pas :** la **définition et la saisie des valeurs d'indicateurs** (Spec 05) — une activité réalisée en est une source ; les **tâches/kanban** (Spec 04) ; le **budget/dépenses** (Spec 06) ; l'**import Kobo** (Spec 07) comme source alternative de réalisations.

**Frontière avec la Spec 02 :** une activité est l'**occurrence datée** d'un nœud `activite` du cadre logique. Plusieurs occurrences (récurrence, story 3.7) peuvent référencer le même nœud. La périodisation des indicateurs (Spec 05) se fonde sur la **date de réalisation**, jamais sur la date de saisie.

---

## 2. Règles de gestion

### Planification (3.1)
- **RGA-01** — Une **activité** appartient à une organisation et à un projet, et référence **un nœud `activite`** du cadre logique (RGP-08). Champs de planification : intitulé, **date(s) prévue(s)** (début, fin optionnelle), **lieu** (unité géo `geo_units` et/ou localité `localities`) et **point cartographique** optionnel (lat/lon), **responsable** (compte ou membre sans compte de l'équipe projet), **participants prévus** (désagrégés, RGA-05), ressources prévues (texte).
- **RGA-02** — La planification est réservée aux rôles habilités (chef de projet, admin ; responsable S&E en appui). Une activité ne peut être planifiée que sur un projet **non clôturé** ; un projet `suspendu`/`clôturé` passe ses activités en lecture seule (héritage RGP-07).
- **RGA-03** — Le **responsable** doit être membre de l'équipe projet (compte ou fiche membre). La liste de sélection est limitée à l'équipe du projet (RGP-12).

### Réalisation (3.2)
- **RGA-04** — L'**agent de terrain** (et les rôles habilités) saisit la réalisation : **date effective de réalisation** (`realized_at`, dans le passé ou le jour même), participants réels désagrégés, description du déroulé, **difficultés rencontrées**, **mesures correctives**. La **saisie différée** est la norme : `realized_at` (date de terrain) est **distincte** de la date de saisie (`created_at`) ; **tous les calculs périodisés se fondent sur `realized_at`**.
- **RGA-05** — **Désagrégation des participants** sur deux axes indépendants : **sexe** (femmes / hommes) et **tranches d'âge** fixes V1 : **0–5, 6–14, 15–24, 25–59, 60+**. Règle de cohérence à la saisie : `somme(sexe) = total` **et** `somme(tranches d'âge) = total`. En cas d'écart : **alerte non bloquante par défaut**, avec option de **blocage strict configurable par organisation** (paramètre d'organisation, RGA-05b). La désagrégation existe en version **prévue** (planification) et **réelle** (réalisation).
- **RGA-05b** — Paramètre d'organisation `enforce_disaggregation` (défaut : `false` = alerte non bloquante ; `true` = blocage strict). Réglable par l'admin dans les paramètres de l'organisation.
- **RGA-06** — Saisie **mobile-first** : les écrans de réalisation sont pleinement utilisables sur smartphone (responsive), avec un minimum de champs obligatoires (date, total, désagrégations, description).
- **RGA-07** — Une activité a un statut d'exécution : `planifiee` → `realisee` (réalisation saisie) ; `planifiee` → `annulee` (avec motif). Une activité `realisee` reste modifiable par les rôles habilités tant que le projet n'est pas clôturé (correction de saisie tracée en audit).

### Justificatifs (3.3)
- **RGA-08** — Des **pièces justificatives** (photos, listes de présence scannées) sont jointes à une réalisation via medialibrary. **Formats limités** (JPEG, PNG, PDF), **taille plafonnée** (ex. 10 Mo/fichier, à confirmer), **compression/optimisation côté serveur** des images. Les pièces sont **internes** : jamais exposées dans la vue bailleur (RGP-16).

### Formulaires papier (3.4)
- **RGA-09** — Génération PDF de **formulaires papier** cohérents avec les écrans de saisie : **fiche d'activité** (identité, cadre logique, date, lieu, responsable, participants prévus) et **liste de présence** (grille à remplir à la main, désagrégation sexe/âge). Sert le circuit « terrain papier → saisie différée ». Généré via dompdf.

### Calendrier et carte
- **RGA-10** — **Calendrier des activités** (story 3.5) : vue mensuelle, filtres par projet et par responsable, création/déplacement (glisser-déposer) — plugin FullCalendar (Filament v5). Inclus dès cette spec.
- **RGA-11** — **Carte des interventions** (story 3.6) : points des activités géolocalisées sur fond **OpenStreetMap** (Leaflet), filtrables par projet/période ; réservée au responsable S&E et à la direction. Inclus dès cette spec.

### Récurrence
- **RGA-12** — **Duplication d'une activité récurrente** (story 3.7, retenue en V1) : à partir d'une activité, générer une **série** d'occurrences (fréquence : hebdomadaire, mensuelle, personnalisée ; nombre d'occurrences) référençant le même nœud du cadre logique ; chaque occurrence a ses propres dates et sa propre réalisation, reliées par `recurrence_group_id`.

### Transversal
- **RGA-13** — Isolation par organisation (RG-02) sur toutes les entités ; global scope `BelongsToOrganization`. Soft deletes ; jamais de suppression physique d'une activité réalisée.
- **RGA-14** — Audit (spatie/activitylog) : planification, réalisation, correction, annulation, ajout/suppression de justificatif. Consultable par l'admin (son organisation).

---

## 3. Modèle de données

```
activities          ulid, organization_id, project_id FK, logframe_node_id FK (type activite),
                    title, planned_start date, planned_end date nullable,
                    geo_unit_id nullable, locality_id nullable, latitude nullable, longitude nullable,
                    responsible_user_id nullable, responsible_team_member_id nullable,
                    planned_resources text nullable,
                    status (planifiee|realisee|annulee),
                    realized_at date nullable, description text nullable,
                    difficulties text nullable, corrective_measures text nullable,
                    cancel_reason text nullable,
                    recurrence_group_id ulid nullable (série, RGA-12),
                    created_by FK users, timestamps, soft_deletes

activity_disaggregations  ulid, organization_id, activity_id FK,
                    phase (planned|real), dimension (sex|age), key, count
                    — cohérence : somme par dimension = total de la phase (RGA-05)

(justificatifs)     via medialibrary : collection « justificatifs » sur activity
                    (JPEG/PNG/PDF, taille plafonnée, images optimisées) — RGA-08
```

Relations : project 1-n activities ; logframe_node (activite) 1-n activities ; activity 1-n disaggregations ; activity 0..1 responsable (compte **ou** membre) ; activity n medias (justificatifs). `realized_at` porte la périodisation (indexé) ; `recurrence_group_id` relie les occurrences d'une série.

**Note :** la désagrégation à deux axes indépendants (sexe, âge) est stockée normalisée pour préparer l'agrégation des indicateurs (Spec 05, désagrégations sexe/âge/localité) sans refonte. Le total d'une phase est la valeur pivot ; les axes doivent y correspondre.

---

## 4. Workflows et statuts

**Activité :** `planifiée` →(saisie de réalisation)→ `réalisée` ; `planifiée` →(motif)→ `annulée`. Une `réalisée` reste corrigible (audit) tant que le projet n'est pas clôturé. La lecture seule s'hérite du statut du projet (RGP-07).

**Saisie différée :** `realized_at` (terrain) ≤ aujourd'hui, indépendante de `created_at` (saisie). Toute la périodisation aval s'appuie sur `realized_at`.

---

## 5. Écrans (Filament)

**Panel App (tenant) — groupe « Projets » (ou onglet de la fiche projet) :**
1. *Activités* — table (intitulé, projet, nœud du cadre logique, date prévue, date de réalisation, lieu, responsable, statut) filtrable par projet, statut, responsable, période. Aussi accessible en **onglet de la fiche projet** (RelationManager).
2. *Planifier une activité* — formulaire (Section pleine largeur + Fieldsets : Rattachement au cadre logique, Dates, Lieu + point carte, Responsable, Participants prévus désagrégés, Ressources ; labels FR).
3. *Saisir la réalisation* — écran **mobile-first** (date effective, participants réels désagrégés avec contrôle de cohérence, description, difficultés, mesures correctives, justificatifs).
4. *Justificatifs* — dépôt de fichiers (formats/тaille limités, compression serveur).
5. *Impression* — actions « Fiche d'activité (PDF) » et « Liste de présence (PDF) » (RGA-09).
6. *Calendrier* — vue mensuelle FullCalendar, filtres projet/responsable (RGA-10, [S]).
7. *Carte des interventions* — points OSM/Leaflet, filtres projet/période (RGA-11, [S]), accès S&E/direction.
8. *Chronogramme (Gantt)* — activités du projet sur un axe temps (dates planifiées), déblocage de la story 2.5.

---

## 6. Matrice de permissions (extrait)

| Action | admin | chef_projet | responsable_se | responsable_financier | agent_terrain | consultant | bailleur |
|---|---|---|---|---|---|---|---|
| Planifier une activité | ✅ | ✅ | appui | — | — | — | — |
| Saisir/corriger une réalisation | ✅ | ✅ | ✅ | — | ✅ | selon affectation | — |
| Joindre des justificatifs | ✅ | ✅ | ✅ | — | ✅ | — | — |
| Annuler une activité | ✅ | ✅ | — | — | — | — | — |
| Voir activités / calendrier | ✅ | équipe | ✅ | équipe | assignées | affectation | — |
| Carte des interventions | ✅ | — | ✅ | — | — | — | — |
| Imprimer fiches/présence | ✅ | ✅ | ✅ | — | ✅ | — | — |

(La visibilité suit le périmètre projet de la Spec 02, RGP-14. Le bailleur ne voit qu'un agrégat via la vue de partage, RGP-16, sans données nominatives ni justificatifs.)

---

## 7. Cas limites et messages

- Désagrégation incohérente (somme sexe ou âge ≠ total) → **alerte** signalant l'axe fautif (ou **blocage** si l'organisation a activé `enforce_disaggregation`).
- `realized_at` dans le futur → refus (la réalisation est un fait passé ou du jour).
- Responsable hors équipe projet → non proposé / refusé.
- Planification sur projet clôturé → interdit ; réalisation sur projet suspendu → lecture seule.
- Justificatif format non autorisé ou trop volumineux → refus avec message.
- Annulation sans motif → blocage.
- Suppression d'une activité réalisée → refus (préférer annulation/correction).
- Duplication en série → génère N occurrences distinctes, chacune à planifier/réaliser indépendamment.

---

## 8. Critères de recette

- Un chef de projet planifie une activité rattachée à un nœud `activite`, avec lieu, point carte, responsable de l'équipe et participants prévus désagrégés cohérents.
- Un agent de terrain saisit une réalisation **en différé** : `realized_at` (passée) distincte de la date de saisie ; la cohérence sexe/âge = total est vérifiée (alerte par défaut, blocage si l'OSC l'a activé) ; une photo et une liste de présence scannée sont jointes (formats/taille contrôlés).
- Les **formulaires papier** (fiche d'activité, liste de présence) s'impriment en PDF, cohérents avec les écrans.
- Le **calendrier** affiche les activités du mois, filtrables par projet/responsable ; la **carte** affiche les points géolocalisés (S&E/direction).
- Le **Gantt** du projet affiche les activités sur l'axe temps (dates planifiées).
- **Isolation (RG-02)** : aucune activité, désagrégation ou justificatif d'une autre organisation n'est atteignable ; le bailleur ne voit ni nominatif ni justificatif.
- **Audit** : planification, réalisation, correction, annulation et gestion des justificatifs sont journalisées.

---

## 9. Décisions actées et points ouverts

**Actées :**
- Une activité = **occurrence datée** d'un nœud `activite` du cadre logique ; la récurrence crée des occurrences distinctes (même nœud).
- **Saisie différée** : `realized_at` distincte de `created_at` ; toute périodisation aval sur `realized_at`.
- Désagrégation **à deux axes indépendants** (sexe, âge), stockée normalisée pour l'agrégation des indicateurs (Spec 05).
- Justificatifs **internes** (jamais dans la vue bailleur), via medialibrary avec formats/taille limités et compression serveur.

- **Tranches d'âge** V1 fixes : **0–5, 6–14, 15–24, 25–59, 60+** (tranché).
- Cohérence désagrégation : **alerte non bloquante par défaut**, **blocage strict configurable par OSC** via `enforce_disaggregation` (tranché — RGA-05/05b).
- **Calendrier (3.5) et carte (3.6) inclus dès Spec 03** (tranché).
- **Récurrence (3.7) retenue en V1** (tranché) : fréquences hebdo/mensuelle/personnalisée, occurrences reliées par `recurrence_group_id`.

**Points ouverts (à trancher au cadrage) :**
- **Taille max** des justificatifs et politique de compression (dimension cible des images) — proposition 10 Mo/fichier.
- Gantt : dépendances entre activités (fin→début simple) — confirmer l'utilité en V1.
