# autopartsonline

VIN-first online shop for car parts, aimed at ordinary drivers (not mechanics).
The customer types their VIN, we identify the exact vehicle, and we show only the
parts that fit it, sourced from partner suppliers.

**Status:** building the MVP. Step 1 of 6 (fitment core) is done; see `docs/10-build-steps.md`.

Stack: Laravel 12, PostgreSQL, Inertia React (storefront and panels).

| Where | What |
|---|---|
| `docs/` | Business concept, discussion prep, supplier questionnaire, product design, architecture, roadmap, stress-test report |
| `docs/reference/` | AutoPartsAPI v2 practical guide (the catalog API we plan to build on) |
| `app/`, `database/`, `tests/` | Laravel application |
| `.claude/skills/` | Repo skills: `/stress-test-product-design`, `/stress-test-project-plan`, `/supplier-readiness-check` |
| `CLAUDE.md` | Project context for Claude Code sessions |

Start with `docs/README.md`.

## Run it locally

Needs PHP 8.3, Composer, Node 22 and PostgreSQL 16 with the `pg_trgm` and `unaccent` extensions.

```bash
composer install && npm ci && npm run build
cp .env.example .env && php artisan key:generate
# create databases "autoparts" and "autoparts_test" owned by user "autoparts", set DB_PASSWORD in .env
php artisan migrate --seed          # Albanian categories + demo vendor on the fake catalog
php artisan catalog:vin WVWZZZAUZFW000001 ferrota
php artisan test
```

The fake catalog (`CATALOG_DRIVER=fake`) uses invented data and is what you build against
until the MEGA plan is active. Demo VINs: `WVWZZZAUZFW000001` (one Golf),
`WVWZZZAUZGW000002` (two Golf engines), `WDD2040021A000003` (Mercedes C-Class).
Demo logins after seeding: `admin@autoparts.test` and `vendor@autoparts.test`, password `password`.
