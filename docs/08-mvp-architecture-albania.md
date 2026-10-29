# 08 — MVP architecture (Albania, solo developer, merchant model)

Decisions this doc assumes (logged in doc 02): market Albania, founder builds, we are the
merchant (not a marketplace), three panels (admin/helpdesk, customer, vendor), transport
handled later, a human technician behind the shop.

## 1. The one decision that shapes everything: link on upload, not on search

**Link the vendor's parts to the catalog when the file is uploaded, and build a fitment index
in our own database then. At search time, call the catalog API only to decode the VIN.**

Why:

| | On upload (chosen) | On the fly at search |
|---|---|---|
| Search latency | One DB query | 5 to 15 API calls per search, each 100 to 500 ms |
| API quota | Spent once per SKU, then only on new SKUs | Spent on every search, including bots and bounces |
| Works when API is down | Yes, except new VINs | No |
| Shows only what vendor has | Natural: index is built from the vendor's file | Must filter after fetching |
| "Reverse view" (this vendor part = this catalog part = these cars) | Already stored | Must be re-fetched |
| Cost | Cheap, predictable | Scales with traffic, not with sales |

The index is **article-centric**: for every vendor item that matched a catalog article, fetch
the list of vehicles that article fits (`get-compatible-cars-by-article-number`, one call per
article) and store `(article_id, vehicle_id)` rows. A vendor with 5,000 SKUs costs roughly
5,000 + 5,000 calls once (match + fitment), then only the delta on each new file.

The vehicle-centric alternative (for every vehicle × category, list articles) needs tens of
thousands of calls and most results would be parts the vendor does not stock. Do not do it.

Licence note: this stores catalog data (article names, OE numbers, fitment lists). Confirm in
writing with the provider that caching for a shop is allowed (doc 01, A7). If they say no, the
same architecture works with a 24-hour cache TTL and background refresh; only the retention
changes.

## 2. Stack (one deployable, one database)

> **Superseded on 2026-10-29:** the stack is one Laravel app on Postgres, storefront in
> Inertia React with SSR, admin and vendor panels in Filament, Laravel database queue and
> scheduler. The Next.js notes below are kept for reference. Matches are stored in a shared
> `part_matches` table keyed by brand + normalised number. Flows: `docs/09-core-flows.md`.

- **Next.js (App Router, TypeScript)** for storefront, the three panels, and API routes. One
  repo, one deploy. Server components keep the catalog key server-side by construction.
- **PostgreSQL** for everything, including the cache and the job queue at MVP stage. Add Redis
  only when you measure a need. Use `pg_trgm` for fuzzy search over Albanian names and
  synonyms, and `unaccent`.
- **Drizzle or Prisma** for schema and migrations (pick whichever you already know).
- **Background worker**: a `jobs` table plus one Node process (`node worker.js`) that polls it.
  Jobs: `import_vendor_file`, `match_item`, `fetch_fitment`, `refresh_article`. No queue
  infrastructure for the MVP.
- **Auth**: one `users` table with `role in (customer, vendor, admin)`. Email + password or
  magic link. Phone number mandatory for customers (delivery and WhatsApp).
- **Payments**: cash on delivery as the default order type for Albania. Add card later.
- **Hosting**: any VPS or a Node PaaS plus managed Postgres. Images: do not copy the catalog
  CDN images; hot-link them via the media endpoint unless the licence says you may store them.

## 3. Data model (the tables that matter)

```
vendors            id, name, contact, file_format_profile (json: column mapping), active
vendor_items       id, vendor_id, raw_brand, raw_article_no, raw_name, ean, price, qty,
                   lead_days, norm_article_no, status (unmatched|matched|manual|ignored),
                   article_id (nullable), match_method (exact|ean|manual), updated_at
brands             id (= catalog supplierId), name, aliases text[]        -- from /suppliers/list
articles           id (= catalog articleId), brand_id, article_no, name, ean, oe_numbers text[],
                   category_id, image_url, raw json, fetched_at
article_vehicles   article_id, vehicle_id                                -- the fitment index
vehicles           id (= catalog vehicleId), manufacturer, model, name, kw, hp, cc, fuel,
                   year_from, year_to, type_id, raw json, fetched_at
vin_lookups        vin, result json, vehicle_ids int[], decoder, created_at   -- 90-day cache
categories         id (= catalog categoryId), parent_id, name_en
category_i18n      category_id, lang, name, slug, synonyms text[]       -- Albanian slang here
offers (view)      vendor_item where status=matched and qty>0 → article_id, price, vendor_id
orders             id, customer_id, vehicle_id, vin, status, payment (cod), total, fit_check
order_items        order_id, article_id, vendor_item_id, qty, price_at_order
fit_checks         order_id, assigned_to, status, notes                 -- technician queue
tickets            customer_id, order_id?, channel (whatsapp|form), status, thread json
jobs               id, type, payload json, status, attempts, run_after, last_error
```

`offers` is a view, so stock and price are always the vendor's latest file, and only matched
items can ever be sold.

## 4. The vendor import pipeline

```
vendor uploads CSV/XLSX (or we pull their API)
   └─► parse with the vendor's column profile  → vendor_items (upsert by vendor_id + norm_article_no)
         └─► for each new/changed item: job match_item
               1. normalise: uppercase, strip spaces/dots/dashes  (e.g. "0 986 494 123" → "0986494123")
               2. brand → brand_id via brands.aliases (BOSCH, Bosch, BSH …)
               3. exact:  GET search-by-articles-no-supplier-id (articleNo, supplierId)
               4. else EAN: artlookup search-for-cross-numbers (EAN)
               5. else: search-by-article-no (no brand) → if exactly one brand hit, mark "manual"
                  and put in admin review queue with the candidate; else "unmatched"
               6. on match: upsert articles row, job fetch_fitment(article_id)
         └─► fetch_fitment: GET get-compatible-cars-by-article-number → article_vehicles rows,
             upsert vehicles rows with the vehicle summary
```

Admin sees three lists: matched (with confidence), needs review (one candidate, confirm or
reject), unmatched (fix the brand alias or article number, or ignore). The first file from a
vendor will need an hour of alias fixing; after that it is mostly automatic.

Vendor with an API instead of a file: same pipeline, the "parse" step is an adapter that
pulls and produces the same `vendor_items` rows. Write the adapter per vendor; do not try to
be generic yet.

## 5. The customer search flow (fast path)

```
1. VIN entered → validate (17 chars, auto-fix O→0, I→1) → vin_lookups cache hit? done.
2. Cache miss → vin-check (native, gives vehicleId); if none → decoder-v5 → map to candidates
   via manufacturer + model + year + kW; if still none → guided picker (make → model → year →
   fuel → kW), lists from cached manufacturer/model/vehicle tables.
3. Vehicle confirmed → ONE query:
     select category, count(*) from offers o
       join article_vehicles av on av.article_id = o.article_id
       join articles a on a.id = o.article_id
      where av.vehicle_id = $1 group by a.category_id
   → the category grid shows only categories the vendor can actually sell for this car.
4. Category chosen → same join, list offers, ranked: in stock, brand tier, price.
5. Product page: left = vendor data (name, price, stock, lead time); right = catalog reference
   (brand, article no, OE numbers, images, "fits these cars" list). This is the "part 1234 at
   the vendor = catalog part 1234 = these cars" view you described.
6. Add to cart → checkout (phone, address, cash on delivery) → order → fit_checks row
   (every order goes to the technician at launch; later only flagged ones).
```

Zero catalog API calls in steps 3 to 6. Step 2 is the only live call, cached for 90 days.

Fitment criteria (left/right, front/rear, brake system) are **not** in the MVP flow. At
launch the technician resolves them in the fit check, by phone or WhatsApp if needed, before
dispatch. Log every question asked; that log becomes the question table in phase 2.

## 6. Albania specifics

- **VIN is the key.** No registration-number lookup exists for Albania in the API. Most of the
  fleet is used imports from Germany, Italy, Switzerland and Greece, so the German market
  catalog (`countryFilterId` 63 in the docs, confirm) is the sensible filter. Test the Albanian
  top models in the 50-VIN test: Mercedes C/E-Class and W-series, VW Golf/Passat, BMW 3/5,
  Audi A4/A6, Opel Astra/Corsa, Fiat Punto, Ford Focus, Toyota Yaris.
- **Cash on delivery** is the default. Card checkout can wait.
- **Language**: Albanian only in the UI. Catalog names come in English or German from the
  API; show the Albanian category name and keep the catalog name as a secondary line.
- **Delivery**: later, but the order model already carries address and a status for it.

## 7. Albanian vocabulary and SEO

Store the vocabulary in `category_i18n` and never hard-code it:

| Catalog category (en) | Albanian name | Slug | Synonyms (search + SEO) |
|---|---|---|---|
| Brake pads | Ferrota | ferrota | ferodo, pllaka frenash, brake pads |
| Brake disc | Disk freni | disk-freni | diskofren, disqe frenash, disk frenash |
| Oil filter | Filtër vaji | filter-vaji | filtri i vajit |
| Air filter | Filtër ajri | filter-ajri | filtri i ajrit |
| Cabin filter | Filtër kabine | filter-kabine | filtër poleni, filtri i kondicionerit |
| Fuel filter | Filtër karburanti | filter-karburanti | filtër nafte, filtër benzine |
| Wiper blades | Fshirëse xhamash | fshirese-xhamash | fshirëse, spazzole |
| Spark plugs | Kandela | kandela | candele, spark plugs |
| Battery | Bateri | bateri | akumulator |
| Shock absorber | Amortizator | amortizator | amortizatorë |
| Timing belt | Rrip motori | rrip-motori | cinghia, rripi i shpërndarjes |
| Clutch | Friksion | friksion | frizione, kompleti i friksionit |

(Treat this table as a starting point to check with the technician; add Italian loanwords,
they are common in Albanian garage talk.)

SEO pages generated from the index, all in Albanian, all server-rendered:

- `/pjese/{category-slug}` — category landing ("Ferrota për çdo makinë").
- `/pjese/{category-slug}/{make-slug}/{model-slug}` — "Ferrota për Volkswagen Golf VII", listing
  the offers the vendor actually has for the vehicles of that model. Generate only where
  offers exist, so there are no empty pages.
- `/pjese/{brand}/{article-no}` — the product page with the fitment list (long-tail: people
  search part numbers).
- Search box uses `pg_trgm` over `category_i18n.name + synonyms` and `articles.article_no +
  oe_numbers`, so "diskofren golf 7" and "0986494123" both work.

## 8. The three panels (MVP scope only)

| Panel | Must have at launch | Later |
|---|---|---|
| Admin / helpdesk (you + technician) | Orders list with fit-check queue, vendor items review (matched / review / unmatched), brand aliases editor, category_i18n editor, VIN lookup tool (type a VIN, see what the customer would see), tickets | Reports, returns, question table |
| Customer | VIN → vehicle → categories → offers → product → cart → COD checkout; order status page by phone number + order id; "my cars" | Accounts, history, reviews |
| Vendor | Upload file, see import result (matched / unmatched counts), see their orders to fulfil, mark as shipped | API connection settings, stock editing |

## 9. Build order (you, full-time, about 6 weeks)

1. **Week 1** — Repo, Postgres, schema, catalog adapter (auth header, 429 backoff, quota
   counter, response cache table), `brands` and `vehicles` reference load, CLI: `vin <VIN>`.
   Run the 50-VIN test with real Albanian VINs. This is also gate 0.
2. **Week 2** — Vendor import: file profile, parse, normalise, match jobs, fitment jobs, worker.
   Admin review lists. Run the vendor's real file; measure match rate.
3. **Week 3** — Storefront: VIN → confirm → category grid → offers → product page (two-column
   vendor vs catalog view). Albanian category_i18n seeded.
4. **Week 4** — Cart, COD checkout, orders, fit-check queue, order status page, WhatsApp link
   carrying VIN + part.
5. **Week 5** — Vendor panel (upload, results, orders to fulfil). Tickets. SEO routes and
   sitemap. Basic analytics.
6. **Week 6** — Polish, mobile pass, soft launch to friends and the vendor's walk-ins.

Cut anything that threatens week 4. Weeks 5 and 6 can slip; week 4 is the shop.

## 10. Things to check with the API provider before week 2

1. Licence to store article names, OE numbers and fitment lists in our DB.
2. Plan pricing and quota: estimate 2 calls per SKU for the first import, then VIN decodes
   (about 1 per new visitor) and a daily refresh of changed SKUs.
3. Does `get-compatible-cars-by-article-number` return all vehicles or a page? (Docs do not
   say; this decides whether the fitment index is complete.)
4. Does `vin-check` return a `vehicleId` directly for the Albanian fleet's typical cars?
5. Correct `countryFilterId` for Albania, or confirm that 63 (Germany) is the best proxy.
