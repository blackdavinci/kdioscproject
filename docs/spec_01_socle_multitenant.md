# SPEC 01 — Socle multi-tenant, comptes, rôles et référentiels

**Version :** 1.0 — Juillet 2026 · **Réf. :** CDC v2.0, Backlog V1 (Epic 1, stories 1.1–1.8)
**Statut :** à valider avant développement

---

## 1. Objectif et périmètre

Ce module fonde la plateforme : organisations (tenants), comptes utilisateurs et authentification, rôles et permissions, annuaire des membres d'équipe sans compte, référentiels d'organisation (étiquettes, secteurs, bailleurs) et référentiel géographique national. Tout autre module en dépend.

**Couvre :** stories 1.1 à 1.7. **Ne couvre pas :** flex fields (1.8, W), le rôle bailleur dans son usage projet (spec 02), les notifications (spec 08).

---

## 2. Règles de gestion

### Organisation et isolation
- **RG-01** — Toute donnée métier appartient à exactement une organisation (`organization_id` obligatoire). Seuls le référentiel géographique national et les données du panel super-admin sont hors tenant.
- **RG-02** — L'isolation est garantie à deux niveaux : tenancy Filament (`->tenant(Organization::class)`) et global scope Eloquent (trait `BelongsToOrganization`) actif partout (jobs, commandes, exports, API). Une suite de tests Pest dédiée vérifie : accès direct par URL/ULID d'un autre tenant → 404 ; recherche, autocomplete de mentions, exports et notifications ne renvoient jamais de données d'un autre tenant.
- **RG-03** — Identifiants : toutes les entités exposées (URL, exports, mapping Kobo) utilisent des **ULID** comme clé primaire (`HasUlids`). Les tables pivot et de mesures à fort volume peuvent conserver des bigint internes, jamais exposés.
- **RG-04** — Statuts d'une organisation : `active` / `suspendue`. La suspension (décidée par le super-admin) bloque la connexion de tous ses membres avec un message explicite, conserve toutes les données, et n'interrompt pas les sauvegardes. Réactivation possible à tout moment.
- **RG-05** — Paramètres d'organisation : nom, sigle, logo, contacts, devise d'affichage (GNF par défaut, informatif), année fiscale (mois de début), fuseau (Africa/Conakry fixe en V1).

### Comptes et authentification
- **RG-06** — L'identifiant de connexion est l'**adresse e-mail, unique sur toute la plateforme**. En V1, un compte appartient à une seule organisation. (Limitation assumée : une même personne intervenant dans deux organisations utilise deux adresses. Multi-appartenance = V2.) Le numéro de téléphone est un champ de contact, pas un identifiant.
- **RG-07** — Création de compte uniquement **sur invitation** (pas d'inscription libre) : l'admin saisit e-mail + rôle → e-mail avec lien signé, valable **72 h**, renvoyable ; le compte s'active à la définition du mot de passe. Une invitation expirée est ré-émissible ; une invitation vers un e-mail déjà titulaire d'un compte (quelle que soit l'organisation) est rejetée côté serveur avec un message générique n'exposant pas l'existence du compte (anti-énumération).
- **RG-08** — Mot de passe : 10 caractères minimum, vérification contre les listes de mots de passe compromis (règle `uncompromised` de Laravel). Verrouillage temporaire progressif après 5 échecs. Réinitialisation par e-mail (lien signé 60 min).
- **RG-09** — **2FA TOTP** (app d'authentification, via Breezy) : obligatoire pour les rôles admin d'organisation et super-admin (blocage du panel tant que non configurée), proposée aux autres rôles. À l'activation, des **codes de secours** sont générés et leur téléchargement/impression est imposé avant de poursuivre — condition de survie pour un admin en zone rurale qui perd son téléphone. Régénération possible depuis le profil (invalide les anciens).
- **RG-10** — Comptes `consultant` et `bailleur` : date d'expiration **obligatoire** (max +12 mois), contrôlée par middleware à chaque requête ; à échéance, sessions révoquées immédiatement et statut `expiré` (réactivable par l'admin avec nouvelle date). **Fraîcheur du contrôle :** le statut et l'expiration sont relus depuis la base (ou un cache Redis ≤ 60 s), jamais depuis la seule session — indispensable avec FrankenPHP en mode worker et les requêtes Livewire fréquentes : une désactivation ou expiration coupe la toute prochaine interaction à l'écran.
- **RG-11** — Désactivation d'un compte : possible à tout moment par l'admin ; révoque les sessions, conserve tout l'historique (auteur des commentaires, saisies, audit). Jamais de suppression physique d'un compte ayant une activité. Interdiction de désactiver ou rétrograder **le dernier admin actif** d'une organisation (blocage avec message).

### Rôles et permissions
- **RG-12** — 7 rôles par organisation, implémentés via spatie/laravel-permission en mode *teams* (team = organisation) : `admin`, `chef_projet`, `responsable_se`, `responsable_financier`, `agent_terrain`, `consultant`, `bailleur`. Le détail des permissions par module est défini dans chaque spec ; la matrice du socle figure en §6.
- **RG-13** — Les rôles sont fixes en V1 (pas de création de rôles personnalisés). Un utilisateur a exactement un rôle. Le rôle `bailleur` ne voit que les projets explicitement partagés avec lui (mécanique en spec 02).
- **RG-14** — Le **super-admin** (opéré par KIDIANI) vit dans un panel séparé (`/admin`), hors tenancy. Il gère les organisations et la santé de la plateforme mais **n'accède pas aux données métier par défaut**. Un « accès d'assistance » à une organisation est possible : activé explicitement, limité à 24 h, porté par un **identifiant de session d'assistance distinct** (chaque entrée d'audit distingue sans ambiguïté une action de l'opérateur d'une action d'un membre de l'organisation), avec **bandeau persistant** affiché à tous les utilisateurs de l'organisation : « Un accès d'assistance technique par [opérateur] est actif — expire dans X h », et entrée d'audit à l'ouverture comme à la clôture.

### Membres d'équipe sans compte
- **RG-15** — Entité `TeamMember` (membre d'équipe) : nom, fonction, téléphone, localité — rattachée à l'organisation, assignable aux activités et tâches, **sans identifiants de connexion**. Créée/gérée par admin et chefs de projet.
- **RG-16** — Un `TeamMember` peut être **lié ultérieurement à un compte** (`team_member.user_id` nullable) : si la personne reçoit une invitation, l'admin lie le nouveau compte à sa fiche existante — tout l'historique d'assignations et de réalisations est conservé sans migration de données. Un compte est lié à au plus une fiche et réciproquement. **Prévention des doublons :** le formulaire d'invitation propose de rattacher le futur compte à une fiche membre existante (recherche par nom) *avant* d'en créer une nouvelle. **Fusion :** si un doublon existe malgré tout (fiche manuelle Y + fiche X auto-créée à l'invitation), une action « fusionner » réassigne toutes les références (tâches, activités, réalisations) de Y vers X puis archive Y — opération journalisée et confirmée avec le décompte des objets réassignés.
- **RG-17** — Chaque utilisateur invité se voit automatiquement créer sa fiche TeamMember liée (l'annuaire d'équipe est l'union : comptes + membres sans compte).

### Référentiels d'organisation
- **RG-18** — **Étiquettes** (labels) : référentiel fermé géré par l'admin (nom + couleur), utilisables sur tâches (V1) et extensibles. Pas de création à la volée par les utilisateurs. Suppression d'une étiquette utilisée → détachement (avec confirmation indiquant le nombre d'objets concernés).
- **RG-19** — **Secteurs d'intervention** : liste nationale par défaut fournie au seed (santé, éducation, WASH, gouvernance, agriculture, environnement, protection, moyens d'existence…), complétable par l'organisation.
- **RG-20** — **Bailleurs** : référentiel d'organisation (nom, sigle, type : multilatéral / bilatéral / fondation / privé / public national). Liste nationale de bailleurs courants fournie au seed, complétable. (Le rattachement bailleur ↔ projet est en spec 02.)

### Référentiel géographique national
- **RG-21** — Source de vérité : **COD-AB Guinée (OCHA/PAM, HDX)**, 4 niveaux — région (ADM1), préfecture (ADM2), sous-préfecture/commune (ADM3), district/quartier (ADM4, édition 2021) — avec **P-codes** conservés comme identifiants stables. Modèle : table unique `geo_units` (ulid, pcode unique, name, level 1–4, parent_id, geom nullable). Les géométries (shapefiles) sont importées pour usage cartographique ultérieur.
- **RG-22** — Le référentiel national est **en lecture seule** pour les organisations. Mise à jour par le super-admin lors des nouvelles éditions COD-AB (import idempotent par P-code : ajouts et renommages appliqués, jamais de suppression physique — unités retirées marquées `inactive`).
- **RG-23** — **Localités** : sous le niveau 4, chaque organisation peut créer ses localités propres (villages, quartiers non codifiés) rattachées à une unité ADM4 (ou ADM3 si l'ADM4 manque) : `localities` (organization_id, geo_unit_id, name, point géographique optionnel). Utilisées partout où un lieu est demandé. Le modèle `Locality` porte le global scope `BelongsToOrganization` : dans tout sélecteur de lieu, un utilisateur voit le référentiel national commun (`geo_units`) **plus ses seules localités d'organisation** — jamais celles d'une autre (cas couvert par la suite de tests d'isolation, RG-02).
- **RG-24** — La liste manuelle des sous-préfectures fournie au cadrage sert de **contrôle croisé** de l'import COD-AB ; tout écart (orthographe, unité manquante) est journalisé dans le rapport d'import et arbitré manuellement.

### Transversal
- **RG-25** — Soft deletes sur toutes les entités du socle ; les suppressions physiques sont réservées aux données jamais référencées.
- **RG-26** — Audit (spatie/activitylog) : création/modification/suppression des organisations, comptes, rôles, référentiels, et tout accès d'assistance super-admin. Consultable par l'admin d'organisation (périmètre : son organisation) et le super-admin (périmètre : plateforme).

---

## 3. Modèle de données (socle)

```
organizations   ulid, name, sigle, logo, contacts(jsonb), currency, fiscal_year_start,
                status(active|suspended), settings(jsonb), timestamps, soft_deletes

users           ulid, organization_id FK, team_member_id FK **NOT NULL** (fiche créée en
                transaction avec le compte — l'annuaire est l'union stricte, aucun compte
                « flottant »), email unique, password,
                role (spatie, team=organization_id), phone, locale, two_factor_*,
                backup_codes (chiffrés),
                status(invited|active|disabled|expired), expires_at nullable,
                last_login_at, timestamps, soft_deletes

invitations     ulid, organization_id, email, role, token hash, expires_at,
                sent_by FK users, accepted_at nullable

team_members         ulid, organization_id, user_id nullable unique, full_name, function,
                phone, locality_id nullable, notes, timestamps, soft_deletes

tags            (spatie/tags, scopé organization) name, color
sectors         ulid, organization_id nullable (null = national), name
donors          ulid, organization_id, name, sigle, type

geo_units       ulid, pcode unique, level(1-4), parent_id FK self, name,
                geom geometry nullable — **index GIST créé dès la migration**
                (requis pour les futures requêtes spatiales des cartes S&E), active bool
localities      ulid, organization_id, geo_unit_id FK, name, point geometry nullable
```

**Note d'architecture — Row Level Security PostgreSQL :** la RLS n'est pas activée en V1, choix documenté : l'application accède à la base via un utilisateur unique poolé, ce qui imposerait de propager le tenant par variable de session à chaque connexion — complexité et risques de régression disproportionnés face à la double protection applicative existante (tenancy Filament + global scope), elle-même couverte par une suite de tests dédiée. La RLS reste une couche de défense en profondeur envisageable en V2 si un audit l'exige ; le schéma (organization_id systématique) la permet sans refonte.

Relations clés : organization 1-n users/team_members/donors/localities ; user 1-1 team_member (obligatoire) ; team_member 0..1 user ; geo_units arbre par parent_id ; tout le reste des modules référencera organization_id + (geo_unit_id|locality_id) pour les lieux.

---

## 4. Workflows et statuts

**Compte utilisateur :** `invité` →(activation lien)→ `actif` ⇄(admin)→ `désactivé` ; `actif` →(échéance, rôles temporaires)→ `expiré` →(admin, nouvelle date)→ `actif`. Toute sortie de `actif` révoque les sessions.

**Organisation :** `active` ⇄(super-admin, motif obligatoire)→ `suspendue`.

**Invitation :** `envoyée` →(clic + mot de passe)→ `acceptée` | →(72 h)→ `expirée` →(renvoi)→ `envoyée`.

---

## 5. Écrans (Filament)

**Panel App (tenant), section « Organisation » — accès admin sauf mention :**
1. *Paramètres de l'organisation* — page settings (profil, devise, année fiscale, logo).
2. *Utilisateurs* — table (nom, e-mail, rôle, statut, dernière connexion, expiration) ; actions : inviter (modal e-mail + rôle + expiration si rôle temporaire), renvoyer l'invitation, désactiver/réactiver, changer le rôle, lier à une fiche membre.
3. *Membres d'équipe* — table des membres (team_members) (nom, fonction, téléphone, localité, compte lié ou —) ; CRUD ; visible aussi par chefs de projet (création autorisée).
4. *Référentiels* — trois onglets : Étiquettes (nom, couleur, nb d'usages), Secteurs, Bailleurs.
5. *Localités* — table filtrée par l'arbre géo national (sélecteur région→préfecture→sous-préf.→district), création de localités.
6. *Journal d'audit* — table filtrable (utilisateur, type d'objet, action, période).
7. *Mon profil* (tous rôles, via Breezy) — infos, mot de passe, 2FA.

**Panel Admin (super-admin, KIDIANI) :**
8. *Organisations* — table (nom, statut, nb utilisateurs, création) ; créer + inviter le premier admin (story 1.1) ; suspendre/réactiver (motif) ; accès d'assistance 24 h (confirmation + journalisation).
9. *Référentiel géographique* — import/mise à jour COD-AB (upload XLSX/SHP, rapport d'import, écarts).
10. *Santé & sauvegardes* — plugins health + backup.

---

## 6. Matrice de permissions (socle)

| Action | admin | chef_projet | resp_se | resp_fin | agent | consultant | bailleur |
|---|---|---|---|---|---|---|---|
| Paramètres organisation | ✔ | – | – | – | – | – | – |
| Gérer utilisateurs / invitations | ✔ | – | – | – | – | – | – |
| Gérer membres d'équipe (team_members) | ✔ | ✔ | – | – | – | – | – |
| Consulter l'annuaire d'équipe | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | – |
| Gérer étiquettes / secteurs / bailleurs | ✔ | – | – | – | – | – | – |
| Créer des localités | ✔ | ✔ | ✔ | – | – | – | – |
| Consulter le journal d'audit | ✔ | – | – | – | – | – | – |
| Gérer son profil / 2FA | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |

(Les permissions métier — projets, activités, budget… — sont définies dans leurs specs.)

---

## 7. Cas limites et messages

- Invitation vers un e-mail déjà en compte → « Si cette adresse est éligible, une invitation lui a été envoyée. » (aucune fuite d'existence) + notification interne à l'admin émetteur de l'échec réel.
- Lien d'invitation expiré → page dédiée avec bouton « demander un nouveau lien » (notifie l'admin).
- Connexion sur organisation suspendue → « L'accès de votre organisation est temporairement suspendu. Contactez [contact plateforme]. »
- Tentative de désactivation du dernier admin actif → blocage : « Une organisation doit conserver au moins un administrateur actif. »
- Compte expiré en cours de session → déconnexion immédiate à la requête suivante, message d'expiration.
- Liaison team_member ↔ user : si la fiche est déjà liée → blocage ; si le compte est déjà lié à une autre fiche → blocage avec indication.
- Suppression d'une étiquette utilisée → confirmation « utilisée sur N éléments ; elle en sera détachée ».
- Import COD-AB : P-code inconnu → création ; nom modifié → mise à jour + ligne au rapport ; P-code absent de la nouvelle édition → `inactive` (jamais supprimé) ; doublon de nom sous un même parent → signalé au rapport.

---

## 8. Critères de recette

1. Deux organisations A et B peuplées : aucun accès croisé possible (URL directe, recherche, mentions, export, notifications) — suite Pest d'isolation verte.
2. Parcours complet : super-admin crée l'organisation → invite l'admin → l'admin active son compte (2FA imposée) → invite un chef de projet → crée 2 membres sans compte → l'un d'eux est ensuite invité et lié à sa fiche sans perte d'historique.
3. Invitation : lien expirée à 72 h ; renvoi fonctionnel ; e-mail déjà titulaire → message générique.
4. Compte consultant avec expiration à J : à J+1, session révoquée et statut `expiré`.
5. Dernier admin actif indésactivable ; organisation suspendue → connexions bloquées avec message.
6. Référentiel géo : import COD-AB complet (4 niveaux), contrôle croisé avec la liste de cadrage documenté, sélecteur en cascade fonctionnel, création de localité rattachée à un ADM4.
7. Toutes les actions du §6 respectent la matrice (test par rôle).
8. Audit : chaque action d'administration du socle apparaît au journal, y compris l'accès d'assistance super-admin (visible par l'admin de l'organisation).

---

## 9. Décisions actées et points ouverts

**Actées :** e-mail comme identifiant unique (pas d'auth téléphone en V1) · super-admin = KIDIANI · référentiel géo = COD-AB OCHA 4 niveaux + localités par organisation · ULID exposés · 1 compte = 1 organisation en V1.

**Ouverts (à trancher en atelier pilote) :** liste nationale par défaut des secteurs et des bailleurs (contenu exact du seed) · libellés paramétrables (« bailleur », « bénéficiaire ») — décision de principe prise, périmètre exact des libellés à fixer en spec 02.
