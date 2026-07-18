# BACKLOG V1 — Plateforme de Gestion de Projets et S&E pour OSC/ONG Guinée

**Version :** 1.0 — Juillet 2026 · **Référence :** Cahier des charges v2.0
**Priorisation MoSCoW :** [M] Must (sans quoi pas de mise en production) · [S] Should (fortement attendu, négociable en date) · [C] Could (si le temps le permet) · [W] Won't (explicitement hors V1)

**Usage :** support des ateliers de cadrage avec les OSC pilotes. Chaque story sera détaillée dans une spécification fonctionnelle par module avant développement. Les priorités S/C sont à confronter aux retours des ateliers.

---

## EPIC 1 — Socle multi-tenant, comptes et rôles

- **1.1 [M]** En tant que super-admin plateforme, je peux créer une organisation (nom, sigle, contacts, logo, devise GNF, année fiscale) et inviter son premier administrateur.
  - *Critères :* l'admin invité reçoit un e-mail d'activation ; l'organisation est immédiatement isolée des autres.
- **1.2 [M]** En tant qu'admin d'organisation, je gère les comptes utilisateurs de mon organisation (invitation, désactivation, rôle).
  - *Critères :* 7 rôles disponibles (admin, chef de projet, S&E, financier, agent terrain, consultant, bailleur — lecture seule) ; les comptes consultant et bailleur ont une date d'expiration, contrôlée à chaque requête (middleware) avec révocation immédiate de session à échéance.
- **1.3 [M]** En tant que système, j'isole strictement les données par organisation.
  - *Critères :* multi-tenancy Filament + global scope BelongsToOrganization ; suite de tests automatisés dédiée à l'isolation (accès croisés, recherche, mentions, exports) ; tous les identifiants exposés (URLs, API, mapping Kobo, exports) sont des ULID — jamais d'auto-incrément visible inter-tenants.
- **1.4 [M]** En tant qu'admin d'organisation, je gère un annuaire de « membres d'équipe » sans compte (animateurs, bénévoles) assignables aux activités et tâches.
- **1.5 [M]** En tant qu'utilisateur, je m'authentifie de façon sécurisée et gère mon profil (mot de passe, photo, langue).
  - *Critères :* 2FA disponible, obligatoire pour les rôles admin ; verrouillage après tentatives échouées.
- **1.6 [S]** En tant qu'admin d'organisation, je définis les référentiels de mon organisation : étiquettes (labels fermés), secteurs d'intervention, bailleurs.
- **1.7 [S]** En tant que système, je fournis le référentiel national partagé : découpage administratif guinéen (région → préfecture → sous-préfecture/commune → district/quartier).
- **1.8 [W]** Personnalisation de champs par organisation (flex fields) — V2.

## EPIC 2 — Projets et cadre logique

- **2.1 [M]** En tant que chef de projet, je crée un projet : identification (titre, code, description), objectifs, résultats attendus, groupes cibles, zone d'intervention (référentiel géo), période, bailleur(s) et montants.
- **2.2 [M]** En tant que chef de projet, je fais vivre le cycle de vie du projet : brouillon → validé → en cours → suspendu → clôturé.
  - *Critères :* transitions soumises aux permissions ; dans les petites structures, un profil admin/directeur peut valider ses propres projets (pas de séparation des rôles imposée) ; historique des changements de statut.
- **2.3 [M]** En tant que chef de projet, je construis le cadre logique simplifié : objectifs → résultats → activités, chaque niveau pouvant porter des indicateurs.
- **2.4 [M]** En tant que chef de projet, j'affecte l'équipe projet (comptes et membres sans compte) avec leur rôle dans le projet.
- **2.5 [S]** En tant que chef de projet, je visualise et ajuste le chronogramme du projet en diagramme de Gantt (Frappe Gantt : vue, glisser-déposer des dates, dépendances simples).
- **2.6 [S]** En tant que directeur, je consulte une vue portefeuille : tous les projets, statuts, périodes, montants, avancement, taux de consommation.
- **2.7 [S]** Accès bailleur : en tant qu'admin ou chef de projet, j'invite un compte bailleur en lecture seule sur un ou plusieurs projets explicitement partagés.
  - *Critères :* visibilité limitée aux projets partagés (avancement, activités réalisées, indicateurs, budget synthétique) ; aucun accès aux données personnelles des bénéficiaires, aux commentaires internes ni aux autres projets/bailleurs de l'organisation ; accès révocable à tout moment ; consultations journalisées ; le partage est toujours à l'initiative de l'organisation.
- **2.8 [W]** Gestion documentaire avancée (GED, versionnage) — V2 ; les pièces jointes simples sont couvertes par les modules concernés.

## EPIC 3 — Activités et exécution terrain

- **3.1 [M]** En tant que chef de projet, je planifie une activité : rattachement au cadre logique, dates, lieu (référentiel géo + point carte), responsable, participants prévus (désagrégés), ressources.
- **3.2 [M]** En tant qu'agent de terrain, je saisis la réalisation d'une activité : date effective, participants réels (désagrégés sexe/âge), description, difficultés, mesures correctives.
  - *Critères :* saisie possible sur smartphone (responsive) ; saisie différée autorisée — la date de réalisation (passée) est distincte de la date de saisie, et tous les calculs d'indicateurs périodisés se fondent sur la date de réalisation ; cohérence des désagrégations contrôlée à la saisie (somme hommes/femmes et tranches d'âge = total des participants, blocage ou alerte).
- **3.3 [M]** En tant qu'agent de terrain, je joins des justificatifs (photos, listes de présence scannées) à une réalisation.
  - *Critères :* compression automatique côté serveur ; formats limités ; taille plafonnée.
- **3.4 [M]** En tant que chef de projet, j'imprime les formulaires papier (fiche d'activité, liste de présence) cohérents avec les écrans de saisie, pour le circuit terrain → saisie différée.
- **3.5 [S]** En tant que chef de projet, je consulte le calendrier des activités (vue mensuelle, filtres par projet/responsable, glisser-déposer).
- **3.6 [S]** En tant que responsable S&E, je géolocalise les interventions et les visualise sur carte (points par activité, fond OpenStreetMap).
- **3.7 [C]** En tant que chef de projet, je duplique une activité récurrente (série).

## EPIC 4 — Tâches et collaboration (kanban, commentaires, mentions, étiquettes)

- **4.1 [M]** En tant que membre d'équipe, je gère des tâches rattachées à un projet ou une activité : titre, description, assigné (compte ou membre sans compte), échéance, priorité, étiquettes.
- **4.2 [M]** En tant que membre d'équipe, je visualise les tâches en kanban (colonnes = statuts : à faire / en cours / bloqué / terminé) avec glisser-déposer, filtrable par projet, assigné et étiquette.
- **4.3 [M]** En tant qu'utilisateur, je consulte « mes tâches » toutes sources confondues, triées par échéance.
- **4.4 [M]** En tant que membre d'équipe, je commente une tâche ou une activité et mentionne un collègue avec @.
  - *Critères :* l'autocomplete des mentions est limité aux membres de l'organisation (test d'isolation dédié) ; la personne mentionnée reçoit une notification in-app + e-mail ; les commentaires sont horodatés et non supprimables silencieusement (édition tracée).
- **4.5 [M]** En tant que membre d'équipe, je crée une tâche indépendante, non rattachée à un projet (tâches internes de l'organisation : administratif, régalien, logistique), visible dans « mes tâches » et le kanban avec un filtre « hors projet ».
- **4.6 [S]** En tant que membre d'équipe, je rends une tâche récurrente (mensuelle, trimestrielle, annuelle…) avec rappel à J-X avant l'échéance — ex. payer l'abonnement internet, renouveler l'hébergement, déposer le rapport annuel, renouveler l'agrément.
  - *Critères :* à la clôture d'une occurrence, la suivante est générée automatiquement ; rappel par notification in-app + e-mail à J-X paramétrable.
- **4.7 [S]** En tant que membre d'équipe, je joins des documents à une tâche (contrat, facture, capture) — même mécanique de pièces jointes que les activités.
- **4.8 [S]** En tant que chef de projet, je reçois un récapitulatif des tâches en retard de mon équipe (notification hebdomadaire).
- **4.9 [C]** SMS (Nimba) sur mention pour les tâches à échéance ≤ 48 h — règle exacte à trancher au cadrage (coût).
- **4.10 [W]** Sous-tâches, dépendances entre tâches, estimation en points — V2.

## EPIC 5 — Suivi-évaluation, indicateurs et bénéficiaires

- **5.1 [M]** En tant que responsable S&E, je définis des indicateurs : libellé, niveau du cadre logique, unité, baseline, cibles périodisées, désagrégations (sexe, tranche d'âge, localité).
- **5.2 [M]** En tant que responsable S&E, je saisis les valeurs réalisées avec leur période et leurs désagrégations, et je joins un moyen de vérification (document, photo, soumission Kobo).
- **5.3 [M]** En tant que responsable S&E, je consulte le tableau réalisé vs cible par indicateur, par période, avec taux d'atteinte et graphiques.
- **5.4 [M]** En tant que responsable S&E, je gère plusieurs cadres de résultats (un par bailleur), les mêmes activités et indicateurs pouvant alimenter des cadres différents (mapping n-n).
- **5.5 [S]** En tant que responsable S&E, je tiens un registre des bénéficiaires (identifiant unique par organisation, données minimales, désagrégations) pour limiter le double comptage.
  - *Critères :* détection des doublons probables à la saisie ; comptages « uniques » vs « participations » distincts dans les rapports ; minimisation des données personnelles (loi L/2016/037) ; les champs nominatifs (nom, contact) sont chiffrés au niveau applicatif (encrypted casts) et n'apparaissent jamais dans les rapports ni les exports — seuls les identifiants abstraits et les agrégats désagrégés y figurent ; le niveau exact de pseudonymisation est arbitré au cadrage avec la validation juridique de l'hébergement.
- **5.6 [C]** Alertes automatiques quand un indicateur passe sous un seuil d'atteinte à une échéance donnée.
- **5.7 [W]** Théorie du changement graphique, enquêtes maison, analyses statistiques avancées — V2 / hors périmètre (Kobo + exports couvrent).

## EPIC 6 — Suivi budgétaire

- **6.1 [M]** En tant que responsable financier, je construis le budget d'un projet en lignes budgétaires (rubriques paramétrables), avec répartition par bailleur en cas de cofinancement.
- **6.2 [M]** En tant que responsable financier, je saisis des dépenses rattachées à une ligne et à une activité : montant, date, libellé, justificatif scanné.
- **6.3 [M]** En tant que responsable financier ou directeur, je consulte le tableau budget vs dépensé vs disponible, par ligne / projet / bailleur, avec taux de consommation.
- **6.4 [M]** En tant que système, j'alerte en cas de dépassement ou d'approche de seuil (paramétrable, ex. 80 %) d'une ligne budgétaire.
- **6.5 [S]** En tant que responsable financier, j'enregistre des engagements (dépense prévue non payée) distincts des dépenses réalisées.
- **6.6 [S]** En tant que responsable financier, j'exporte l'état budgétaire au format Excel (pour le comptable et le rapport financier bailleur).
- **6.7 [W]** Comptabilité générale, rapprochements bancaires, paiements, multi-devises avec taux — hors périmètre (cf. CDC §2.2). La devise unique de travail est le GNF ; les montants bailleurs en devise sont saisis à titre informatif.

## EPIC 7 — Intégration Kobo Toolbox

- **7.1 [M]** En tant qu'admin d'organisation, je connecte le compte Kobo de mon organisation (jeton API) et j'associe un formulaire Kobo à un projet/une activité/un indicateur.
- **7.2 [M]** En tant que système, j'importe périodiquement les soumissions Kobo nouvelles et je les rattache aux activités et indicateurs mappés.
  - *Critères :* import idempotent (pas de doublon au re-run) ; journal des imports avec soumissions rejetées et motif ; le mapping champ Kobo → donnée plateforme est configurable sans code ; les imports s'exécutent sur une file Horizon dédiée à basse priorité (1 worker) pour ne jamais dégrader la réactivité de l'application.
- **7.3 [S]** En tant que responsable S&E, je consulte les soumissions importées rattachées à une activité et je peux en corriger le rattachement.
- **7.4 [C]** Modèles de formulaires Kobo prêts à l'emploi (présence, bénéficiaire, mesure d'indicateur) fournis avec la plateforme.
- **7.5 [W]** Constructeur de formulaires intégré (type Bolt) — écarté définitivement, doublonne Kobo.

## EPIC 8 — Rapports et exports

- **8.1 [M]** En tant que chef de projet, je génère le rapport d'activités d'une période (réalisations, participants désagrégés, difficultés) en Excel et PDF.
- **8.2 [M]** En tant que responsable S&E, je génère l'état des indicateurs d'un cadre de résultats (réalisé vs cible) en Excel et PDF.
- **8.3 [M]** En tant que responsable financier, je génère le rapport financier simple d'un projet (budget vs réalisé par ligne) en Excel.
- **8.4 [S]** En tant qu'organisation, je dispose de canevas bailleurs comme modèles d'export — **plafonnés à 3 maximum en V1**, choisis au cadrage parmi les bailleurs actifs en Guinée (UE, PNUD, USAID, GIZ, AFD…). Tout format non retenu passe par l'export Excel brut (8.5) avec mise en page manuelle — garde-fou anti-dérive de périmètre.
- **8.5 [S]** En tant qu'utilisateur autorisé, j'importe/exporte les données de référence en Excel (activités planifiées, bénéficiaires, budget).
- **8.6 [S]** En tant que chef de projet, je génère un rapport narratif pré-rempli au format Word : structure du canevas retenu, tableaux d'indicateurs et de budget déjà remplis, photos des activités de la période insérées, sections narratives laissées à rédiger. L'organisation complète le texte et transmet au bailleur.
  - *Critères :* génération PHPWord fondée sur la date de réalisation des activités ; limité aux canevas retenus en 8.4 ; les photos insérées sont compressées.
- **8.7 [C]** Rapport annuel consolidé multi-projets de l'organisation.

## EPIC 9 — Tableaux de bord et notifications

- **9.1 [M]** Tableau de bord direction : projets en cours, consommation budgétaire globale, indicateurs clés, alertes, dernières activités.
- **9.2 [M]** Tableau de bord chef de projet : avancement des activités, tâches et échéances de l'équipe, budget projet.
- **9.3 [M]** Centre de notifications in-app (cloche) + e-mail : mentions, assignations, échéances, alertes budgétaires, échecs d'import Kobo.
- **9.4 [C]** Rappels d'échéances par SMS (Nimba) — descendu de Should à Could (arbitrage juillet 2026 au profit du rapport narratif 8.6). Si implémenté : quota mensuel de SMS par organisation, décompte visible et blocage à épuisement.
- **9.5 [C]** Tableau de bord S&E dédié (cartes des interventions, atteinte des cibles par zone).

## EPIC 10 — Administration plateforme et exploitation

- **10.1 [M]** En tant que super-admin, je gère les organisations (création, suspension), sans accéder à leurs données métier par défaut (accès d'assistance tracé et temporaire).
- **10.2 [M]** En tant que super-admin, je consulte la santé de la plateforme (health checks) et l'état des sauvegardes applicatives depuis le panel.
- **10.3 [M]** En tant qu'admin d'organisation, je consulte le journal d'audit des actions de mon organisation (filtrable par utilisateur, objet, période).
- **10.4 [M]** En tant qu'équipe technique, je dispose de la chaîne CI/CD complète (tests + analyse statique bloquants, build GHCR, déploiement staging auto / prod manuel) — cf. CDC §7.
- **10.5 [S]** En tant que super-admin, je publie des annonces plateforme (maintenance, nouveautés) — si retenu, sinon V2.

---

## Récapitulatif Won't (hors V1, assumé)

Comptabilité générale · RH complète · intégrations bancaires et mobile money · constructeur de formulaires intégré · application mobile native · interfaces écrites en langues nationales · flex fields · sous-tâches et dépendances de tâches · GED avancée · théorie du changement graphique · multi-devises comptable.

## Ordre de spécification fonctionnelle proposé

1. Socle multi-tenant, comptes, rôles et référentiels (Epic 1) — conditionne tout le modèle de données
2. Projets et cadre logique (Epic 2)
3. Activités et exécution terrain (Epic 3)
4. Tâches et collaboration (Epic 4)
5. Suivi-évaluation et bénéficiaires (Epic 5)
6. Suivi budgétaire (Epic 6)
7. Intégration Kobo (Epic 7)
8. Rapports, tableaux de bord, notifications (Epics 8-9)
9. Administration plateforme (Epic 10)
