# SPEC 10 — Administration commerciale & Facturation SaaS

**Version :** 1.0 — Juillet 2026 · **Réf. :** CDC v2.2 (modèle économique corrigé), Spec 01 (socle)
**Statut :** à valider avant développement
**Périmètre :** V1

---

## 1. Objectif et périmètre

KDI OSC est un **SaaS commercial édité par Kidiani SARL** : les organisations (OSC/ONG) souscrivent un abonnement payant pour accéder à la plateforme. Ce module couvre la **gestion commerciale des tenants** : plans, abonnements, facturation, encaissement en ligne via **Djomy** (mobile money : Orange Money, MTN, Moov), relances de renouvellement et **suspension automatique en cas d'impayé**, en complément de la suspension manuelle du super-admin (RG-04 du socle).

**Couvre :** plan tarifaire, abonnement par organisation, factures, paiements (Djomy + manuel), cycle de vie (essai → actif → impayé → grâce → suspendu), relances, réactivation, écran de paramétrage, page publique de règlement.
**Ne couvre pas :** paliers/quotas métrés (V2), comptabilité générale de Kidiani (hors plateforme), proratisation fine (V1 = période pleine).

**Dépendances :** Spec 01 (organizations, platform_users, SetOrganizationStatus, ApplyTenantState, MailSettings). Réutilise l'intégration Djomy éprouvée du projet `sagefemme`.

---

## 2. Règles de gestion

### Positionnement & isolation
- **RGF-01** — La facturation est une donnée **de plateforme, hors tenancy**, détenue par Kidiani (super-admin, panel `/admin`). Une organisation n'accède **qu'à son propre** abonnement, ses factures et ses paiements (lecture + initiation de paiement), jamais à ceux d'une autre.
- **RGF-02** — Toute somme est en **GNF**, stockée en **entier** (le franc guinéen n'a pas de sous-unité usuelle). Aucune donnée de carte n'est manipulée : l'encaissement passe par Djomy (mobile money).

### Plan et abonnement
- **RGF-03** — **Plan à plat en V1** : un plan unique (nom, prix GNF, périodicité **annuelle**, durée d'essai), paramétrable par le super-admin. Modèle extensible à des paliers en V2. Le prix en vigueur est celui du plan au moment de l'émission de la facture (les factures déjà émises ne sont pas rétro-tarifées).
- **RGF-04** — **Un abonnement par organisation** (relation 1-1). Créé automatiquement à la création de l'organisation (Spec 01, story 1.1) en statut `trial` si une durée d'essai est configurée, sinon `pending`.
- **RGF-05** — Statuts d'un abonnement : `trial` (essai gratuit) → `active` (payé, période en cours) → `past_due` (échéance dépassée, non payé) → `grace` (délai de grâce en cours) → `suspended` (accès coupé) ; `cancelled` possible (résiliation par le super-admin). La réactivation ramène à `active`.

### Facturation
- **RGF-06** — À l'entrée dans une période payante (fin d'essai, ou renouvellement), une **facture** est émise (n° unique séquentiel, montant, période couverte, échéance). Émission **idempotente** : une seule facture ouverte par période et par abonnement.
- **RGF-07** — Un paiement **soldant** une facture prolonge la période d'abonnement de la périodicité du plan et passe l'abonnement à `active`. Le paiement peut être **en ligne (Djomy)** ou **enregistré manuellement** par le super-admin (virement, espèces) — les deux voies produisent le même effet (scénarios B et C en duo).

### Cycle de vie, grâce et relances
- **RGF-08** — À l'échéance non honorée, l'abonnement passe `active → past_due`, puis `grace` pendant le **délai de grâce configuré** (jours), puis `suspended`. La transition est opérée par un **job planifié quotidien** (file Horizon `low`).
- **RGF-09** — La **suspension pour impayé** utilise `SetOrganizationStatus::suspend()` du socle (RG-04) avec une **source `billing`** et un motif explicite ; elle bloque la connexion de tous les membres via `ApplyTenantState` (déjà en place). La suspension **manuelle** du super-admin porte la source `manual`.
- **RGF-10** — **Relances** automatiques (in-app + e-mail à l'admin de l'organisation) selon un **planning configurable** (ex. J-30, J-7, J-0 avant échéance, puis à l'entrée en grâce), avec le lien de paiement.
- **RGF-11** — **Réactivation automatique au paiement** (paramétrable, activée par défaut) : un paiement soldant la facture d'une organisation `suspended` **de source `billing`** la réactive immédiatement (`SetOrganizationStatus::reactivate()`), rétablit `active` et prolonge la période. Un paiement **ne lève jamais** une suspension de source `manual` (décision commerciale/administrative du super-admin).

### Paiement Djomy
- **RGF-12** — L'intégration Djomy est configurée par le super-admin (§7). Les **clés (client_id, client_secret, webhook_secret, partner_domain) sont chiffrées au repos** (contrairement à l'implémentation `sagefemme`, alignées sur le patron `MailSettings` du socle). Environnements **sandbox** et **production**.
- **RGF-13** — Le **webhook Djomy** est vérifié par **signature HMAC** (`X-Webhook-Signature: v1:<hmac_sha256(corps_brut, client_secret)>`), traité de façon **idempotente** (un même événement rejoué ne double pas le paiement) et exécuté sur la file `low`. Une signature invalide → HTTP 401, aucun effet.
- **RGF-14** — La **réconciliation** est possible sans attendre le webhook : au retour de l'utilisateur (returnUrl), la plateforme interroge `GET /payments/{id}/status` et rejoue la logique webhook (idempotente), pour couvrir les webhooks retardés ou perdus.

### Porte de secours
- **RGF-15** — Une organisation `suspended` doit pouvoir **régler pour se réactiver** malgré le blocage de connexion : une **page de règlement accessible sans session** (depuis l'écran de connexion, par identifiant d'organisation + e-mail admin) permet d'initier le paiement Djomy. Sans cela, une OSC suspendue ne pourrait jamais repayer.

### Transversal
- **RGF-16** — Audit (spatie/activitylog) de toutes les actions de facturation : émission de facture, paiement (Djomy/manuel), changement de statut d'abonnement, suspension/réactivation pour impayé, modification de la configuration commerciale.
- **RGF-17** — Reçu/justificatif de paiement téléchargeable en PDF par l'organisation et le super-admin (réutilise le patron `ReceiptPdfService` de `sagefemme`).

---

## 3. Modèle de données (hors tenancy, niveau plateforme)

```
billing_plans     ulid, name, amount_gnf (int), period (year), trial_days (int),
                  is_active bool, timestamps, soft_deletes

subscriptions     ulid, organization_id unique FK, plan_id FK,
                  status (trial|active|past_due|grace|suspended|cancelled),
                  trial_ends_at nullable, current_period_start, current_period_end,
                  grace_until nullable, suspended_source (billing|manual) nullable,
                  cancelled_at nullable, timestamps

invoices          ulid, organization_id FK, subscription_id FK, number unique,
                  amount_gnf (int), currency (GNF), period_start, period_end,
                  status (pending|paid|failed|void), due_date, issued_at, paid_at nullable,
                  timestamps

billing_payments  ulid, invoice_id FK, organization_id FK, amount_gnf (int),
                  method (djomy|transfer|cash), djomy_link_reference nullable,
                  djomy_transaction_id nullable, status (pending|succeeded|failed|cancelled),
                  djomy_response jsonb, recorded_by FK platform_users nullable,
                  paid_at nullable, timestamps

djomy_webhook_events  ulid, event_type, reference, payload jsonb, processed_at,
                  timestamps   — journal idempotent
```

**Paramètres (spatie/laravel-settings, groupe `billing`, clés Djomy chiffrées) :**
```
BillingSettings   djomy_enabled(bool), djomy_environment(sandbox|production),
                  djomy_client_id*, djomy_client_secret*, djomy_api_url,
                  djomy_webhook_secret*, djomy_partner_domain*   (* = chiffrés)
                  grace_days(int), reminder_days_before(array<int>),
                  auto_reactivate_on_payment(bool)
```

Note : le prix, la périodicité et l'essai vivent dans `billing_plans` (paramétrables) ; la **politique** (grâce, relances, réactivation auto) vit dans `BillingSettings`. Les montants exposés/mapping Djomy utilisent des ULID (RG-03 socle).

---

## 4. Workflows et statuts

**Abonnement :**
`trial` →(fin d'essai, facture émise)→ `past_due` →(paiement)→ `active`
`active` →(échéance non payée)→ `past_due` →(délai de grâce écoulé)→ `grace` →(fin de grâce)→ `suspended`
`suspended` →(paiement, source billing)→ `active` (réactivation auto, RGF-11)
`*` →(super-admin)→ `cancelled`

**Facture :** `pending` →(paiement soldé)→ `paid` | →(échec Djomy)→ `failed` (ré-essayable) | →(annulée)→ `void`

**Paiement Djomy :** initiation (`POST /links`) → redirection page Djomy → `SUCCESS`/`FAILED`/`CANCELLED` (webhook + réconciliation) → mise à jour facture + abonnement.

---

## 5. Écrans (Filament)

**Panel Admin (super-admin, Kidiani) :**
1. *Abonnements* — table par organisation (statut, plan, échéance, prochaine relance) ; actions : générer une facture, **pointer un paiement manuel**, suspendre/réactiver (source `manual`), résilier.
2. *Factures* — table (n°, organisation, montant, période, statut, échéance) ; téléchargement PDF.
3. *Paiements* — grand-livre (méthode, montant, référence Djomy, statut, date) filtrable.
4. *Configuration Facturation* — Djomy (activation, environnement, clés chiffrées, URL de webhook à copier) + Politique (prix du plan, essai, délai de grâce, planning de relances, réactivation auto).

**Panel App (admin d'organisation) :**
5. *Abonnement & Facturation* — plan courant, échéance, **bouton « Payer »** (Djomy), liste des factures + reçus PDF, historique des paiements ; **bandeau de relance** en `past_due`/`grace`.

**Public :**
6. *Régler mon abonnement* — page hors session (RGF-15), atteignable depuis la connexion d'une organisation suspendue, initiant le paiement Djomy.

---

## 6. Matrice de permissions

| Action | super-admin | admin OSC | autres rôles OSC |
|---|---|---|---|
| Configurer Djomy / politique commerciale | ✔ | – | – |
| Gérer plans / abonnements / factures (toutes org.) | ✔ | – | – |
| Pointer un paiement manuel | ✔ | – | – |
| Suspendre/réactiver (source manual) | ✔ | – | – |
| Consulter **son** abonnement / factures | ✔ | ✔ | – |
| Initier le paiement de **son** abonnement | ✔ | ✔ | – |

---

## 7. Intégration Djomy (technique — porté de `sagefemme`)

- **Auth :** en-tête `X-API-KEY: {client_id}:{hmac_sha256(client_id, client_secret)}` (+ `X-PARTNER-DOMAIN`) → `POST {base}auth` → `data.accessToken` (mis en cache ~55 min). URLs : sandbox `https://sandbox-api.djomy.africa/v1/`, prod `https://api.djomy.africa/v1/`.
- **Créer un encaissement :** requête authentifiée `POST {base}links` avec `amountToPay, linkName, description, countryCode=GN, usageType=UNIQUE, merchantReference (n° facture), returnUrl, cancelUrl, metadata{invoice_id, subscription_id, organization_id}, phoneNumber` → `data.paymentPageUrl` (redirection) + `data.reference` (stocké dans `djomy_link_reference`).
- **Statut :** `GET {base}payments/{transactionId}/status` → `CREATED|PENDING|SUCCESS|FAILED|CANCELLED|REDIRECTED` ; `GET {base}links/{ref}` pour retrouver les transactions d'un lien.
- **Webhook :** route `POST /webhooks/djomy`, hors auth panel, vérification HMAC (RGF-13), idempotente, traitement sur file `low` ; en cas de succès → `markAsSucceeded` du paiement → solde la facture → prolonge/active l'abonnement → réactive l'organisation si `billing` (RGF-11).
- **Sécurité :** clés chiffrées (RGF-12) ; webhook jamais fiable sans HMAC valide ; jamais de secret dans les logs, exports ou l'audit.

---

## 8. Cas limites et messages

- Organisation suspendue pour impayé et se connectant → message + lien « Régler mon abonnement pour réactiver l'accès » (RGF-15).
- Webhook reçu deux fois (même transaction) → traité une seule fois (idempotence RGF-13), réponse 200 « ignored ».
- Signature webhook invalide → 401, aucun effet, entrée de log d'alerte.
- Paiement Djomy `FAILED`/`CANCELLED` → facture reste `pending`, l'OSC peut réessayer ; le paiement échoué est tracé.
- Paiement reçu alors que l'organisation a été suspendue **manuellement** → facture soldée mais **pas** de réactivation automatique (RGF-11) ; message au super-admin.
- Double facture pour la même période → empêchée (RGF-06 idempotent).
- Djomy désactivé/non configuré → le bouton « Payer » est masqué ; seul le pointage manuel reste possible.

---

## 9. Critères de recette

1. À la création d'une organisation, un abonnement est créé (`trial` ou `pending`), isolé : l'admin OSC ne voit **que** son abonnement/ses factures (test d'isolation).
2. Paiement en ligne : initiation Djomy → webhook `SUCCESS` (HMAC valide) → facture `paid`, période prolongée, abonnement `active`, organisation réactivée si elle était suspendue `billing`.
3. Pointage manuel d'un paiement par le super-admin → même effet que le paiement en ligne.
4. Impayé : à l'échéance + délai de grâce écoulé, le job passe l'abonnement à `suspended` et bloque la connexion des membres (source `billing`).
5. Relances : notifications in-app + e-mail émises selon le planning configuré.
6. Une organisation suspendue peut atteindre la page de règlement hors session et payer pour se réactiver.
7. Un paiement ne lève **pas** une suspension `manual`.
8. Webhook idempotent (rejeu sans double effet) et signature HMAC invalide rejetée (401).
9. Audit : chaque action de facturation apparaît au journal.

---

## 10. Décisions actées

- SaaS **commercial Kidiani** (pas de mutualisation faîtière) · encaissement **Djomy dès le V1** · **plan à plat** V1 (paramétrable) · suspension **double** (auto impayé + manuelle) · **réactivation auto** au paiement · délai de grâce et relances **paramétrables** · clés Djomy **chiffrées**.
- **Points ouverts (défauts à ajuster en configuration) :** prix annuel du plan, durée d'essai, délai de grâce, planning des relances — valeurs de départ proposées : 1 500 000 GNF/an, essai 14 j, grâce 15 j, relances J-30/J-7/J-0 + entrée en grâce.
