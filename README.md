# KDI OSC — Plateforme de gestion de projets et de suivi-évaluation pour les OSC/ONG de Guinée

Plateforme SaaS **multi-organisations** (multi-tenant) qui outille les OSC et ONG guinéennes
pour la gestion quotidienne de leurs projets : tâches, activités, budget, indicateurs — le
rapport bailleur devenant un sous-produit du travail de tous les jours.

> **État du dépôt :** Phase 0 (fondations) + Spec 01 (socle multi-tenant) en cours.
> Les modules métier (projets, activités, tâches, S&E, budget, Kobo, rapports) ne sont **pas**
> encore implémentés — voir `docs/`.

## Stack

| Composant | Choix |
|---|---|
| Backend | PHP 8.3+ · Laravel 13 |
| Admin / UI | Filament 5 (2 panels : `app` tenant, `admin` super-admin) |
| Front | Livewire 3 + Alpine + Tailwind (fournis par Filament, pas de SPA) |
| Base de données | PostgreSQL 16 + PostGIS |
| Cache / files / sessions | Redis + Horizon (files `default` et `low`) |
| Runtime prod | FrankenPHP (conteneur) |
| Tests / analyse / style | Pest · Larastan · Pint |

## Prérequis

- [Docker](https://www.docker.com/) (Laravel Sail — aucun PHP/Postgres local requis)
- ou, en alternative, PHP 8.3+, Composer, PostgreSQL 16 + PostGIS et Redis installés localement

## Installation (Sail)

```bash
git clone <url> kdioscproject && cd kdioscproject
cp .env.example .env
composer install
./vendor/bin/sail up -d           # app + PostGIS 16-3.4 + redis + mailpit
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

Astuce : ajoutez un alias `alias sail='./vendor/bin/sail'` à votre shell.

## Services locaux

| Service | URL |
|---|---|
| Application | http://localhost |
| Mailpit (e-mails de test) | http://localhost:8025 |

## Commandes utiles

```bash
composer test       # suite Pest
composer analyse    # analyse statique Larastan
composer format     # style de code Pint
```

## Structure du projet

```
app/            Code applicatif (modèles, Filament, middlewares, actions…)
config/         Configuration Laravel et packages
database/       Migrations, seeders, factories, données de référence (database/data/)
docs/           Cahier des charges, backlog, spécifications, données géo COD-AB
lang/           Traductions (interface 100 % français)
tests/          Suites Pest (dont l'isolation multi-tenant)
```

## Documentation de référence

- `docs/cahier_charges_v2.md` — cahier des charges
- `docs/backlog_v1_moscow.md` — backlog V1 priorisé (MoSCoW)
- `docs/spec_01_socle_multitenant.md` — spécification du socle en cours d'implémentation
