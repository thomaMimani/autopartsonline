# 10 — Build steps

Each step ends with working, tested code on the branch. Later steps depend only on the
services from step 1, so the UI can change freely.

| Step | Delivers | Status |
|---|---|---|
| 1. Fitment core | Postgres schema (`public` for our data, `catalog` for API data), catalog client with fake and real versions, vendor CSV/XLSX import, matching ladder, fitment job, VIN resolver with cache, database-only part search, CLI tools, tests | **Done** |
| 2. Storefront | Albanian pages: VIN entry, confirm or choose vehicle, category grid, offers (recommended, cheaper, premium), product page with vendor data next to the catalog reference, Inertia SSR on | Next |
| 3. Admin panel | Vendors and file profiles, import history, review queue (confirm or reject candidates), unmatched list with brand-alias editor, category mapping, Albanian vocabulary editor, VIN lookup tool, API usage per day | |
| 4. Orders | Cart, cash-on-delivery checkout, order status page by phone + order number, technician fit-check queue, WhatsApp link carrying VIN and part | |
| 5. Vendor panel | Upload file, see import results, see orders to fulfil, mark shipped | |
| 6. Launch | SEO routes and sitemap, mobile pass, deploy on a VPS with supervisor running the queue worker and the SSR server, switch to the real API on MEGA | |

## Before switching `CATALOG_DRIVER=api`

1. MEGA plan active (terms §5; storage is not allowed below it, in any environment).
2. Call each endpoint used by `ApiProfileCatalogClient` once and fix the field-name lists in
   its `pick()` calls. Do not commit the real responses.
3. Confirm `get-compatible-cars-by-article-number` returns every vehicle, not one page.
4. `php artisan catalog:sync-brands`, then import the vendor's real file and check the
   match rate in the database before opening the shop.
