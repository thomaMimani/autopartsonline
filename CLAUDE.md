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
launch, transport later. Architecture: `docs/08-mvp-architecture-albania.md`.

## Current phase
About to build the MVP (6-week order in doc 08). Read `docs/README.md` first. Decisions already taken are logged in
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
