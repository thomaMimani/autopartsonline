# autopartsonline — project context

## What this is
A VIN-first car-parts e-commerce project. Core promise: a non-mechanic driver enters
their VIN (or registration number), we resolve the exact vehicle, and we show only
parts that fit, with stock and price from partner suppliers (distributors).

Catalog data comes from AutoPartsAPI v2 (apiprofile.com). Practical guide:
`docs/reference/autopartsapi_v2_practical_guide.md`.

## Vocabulary (keep these distinct)
- **Brand** = the part manufacturer (Bosch, Brembo). The catalog API calls this `supplier` / `supplierId`.
- **Vendor** = our stock partner / distributor who sells and ships the part. The founder calls these "suppliers". In code and docs, say **vendor** for stock partners and **brand** for the catalog's supplier to avoid confusion.
- **Catalog vehicle** = a `vehicleId` (an engine/body variant), not a model.
- **Article** = a catalog part record (`articleId`). An **offer** = a vendor's price + stock for an article.

## Decisions taken (2026-10-29)
Market Albania. Founder is the sole developer and the merchant (not a marketplace).
Three panels: admin/helpdesk, customer, vendor. Vendor data arrives by their API or
CSV/XLSX upload. Catalog linking and the fitment index are built **on upload**; the
only live catalog call during a customer search is the VIN decode. Cash on delivery at
launch, transport later. Stack: Laravel 12 + Postgres; storefront and admin/vendor panels all in
Inertia React (storefront with SSR). **No Filament.** Flows: `docs/09-core-flows.md`. Architecture: `docs/08-mvp-architecture-albania.md`.

## Current phase
Building the MVP step by step: `docs/10-build-steps.md`. Step 1 (fitment core) is done.

## Code map
- `app/Catalog/` the catalog client: interface, `FakeCatalogClient` (invented fixtures), `ApiProfileCatalogClient`
- `app/Services/` `VendorImporter` + `VendorFileReader`, `MatchingService` (ladder), `FitmentService`,
  `CatalogStore` (only writer to the `catalog` schema), `VinResolver`, `PartSearch` (DB only), `BrandResolver`
- `app/Jobs/` `ImportVendorFile`, `MatchPart`, `FetchArticleFitment`
- `app/Console/Commands/` `catalog:sync-brands`, `vendor:import`, `catalog:vin`, `catalog:refresh-fitment`, `catalog:purge`
- Tests run on Postgres database `autoparts_test` (`php artisan test`). Read `docs/README.md` first. Decisions already taken are logged in
`docs/02-discussion-prep.md` under "Decision log"; do not re-open them without the
founder asking.

## Repo skills
- `/stress-test-product-design <doc>` — red-team a product/UX design against the non-mechanic customer.
- `/stress-test-project-plan <doc>` — red-team a business or project plan (assumptions, killers, pre-mortem).
- `/supplier-readiness-check <notes>` — score a prospective vendor on data, ops and commercial fit.

## Conventions
- Docs are Markdown under `docs/`, numbered in reading order.
- Never commit API keys. Use `.env` (ignored) and keep `.env.example` current.
- When application code is added, keep catalog API calls server-side only.
- API-derived data goes in the Postgres `catalog` schema only, so it can be purged (terms §5).
  Storing it requires the MEGA plan or higher (production will run on MEGA). Before MEGA
  is active, develop against a fake catalog client; never commit real API responses. No catalog images until cleared. See
  `docs/reference/autopartsapi-terms-analysis.md`.
