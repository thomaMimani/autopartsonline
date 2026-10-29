# 05 — Technical architecture (for when we build; phase 2)

Nothing here is to be built before the phase-1 gate in doc 06 is passed.

## Shape

```
Customer (mobile web)
   │
   ▼
Storefront (Next.js or similar, SSR, mobile first)
   │  our own JSON API
   ▼
Backend (one service to start; Node/TypeScript or Python)
   ├─ Vehicle resolution      VIN / reg number / picker → catalog vehicleId (+ candidates)
   ├─ Catalog adapter         AutoPartsAPI client: auth, rate limit, retries, cache, quota meter
   ├─ Fitment engine          articles + criteria → questions → recommended article(s)
   ├─ Offers                  vendor price/stock import, mapping brand+articleNo → articleId
   ├─ Orders & checkout       cart, payment provider, order → vendor notification, returns
   └─ Admin / ops             human fit-check queue, question table editor, vendor file upload
   │
   ├─ PostgreSQL              vehicles seen, articles cached, offers, orders, question table
   ├─ Redis                   hot cache for catalog responses, rate limiting
   └─ Object storage          cached images (only if the licence allows), vendor files
   │
   ▼
AutoPartsAPI v2 (server-side only, key in secret manager)      Vendor files (CSV/Excel/API)
```

## Key design decisions

### 1. The catalog API is never called from the browser

Key stays server-side (guide, section 10). Every call goes through one adapter that
implements: `x-apiprofile-key` header, 429 backoff with jitter, bounded retries on 500, no
retry on 400/401/403, and a quota counter so we see spend per day and per order.

### 2. Cache aggressively, with full-scope keys

Cache key must include every scoping parameter (`typeId`, `langId`, `countryFilterId`,
`manufacturerId`, `modelId`, `vehicleId`, `categoryId`). TTLs:

| Data | TTL |
|---|---|
| Languages, countries, vehicle types, brands (`/suppliers/list`) | 30 days |
| Manufacturers, models, vehicle variants | 7 days |
| Category tree per vehicle, article list per vehicle+category | 24 h |
| Article details, criteria, media info | 24 h |
| VIN decode result | 90 days (VINs do not change; cuts quota cost on repeat visits) |

Licence check (doc 01, A7) decides whether we may persist article data beyond a cache.

### 3. Vehicle resolution is a pipeline with candidates, not a single lookup

```
input ──► validate (17 chars, no I/O/Q, check digit where applicable)
      ──► cache hit? ──► yes: return
      ──► vin-check (native, returns vehicleId)       ─┐
      ──► decoder-v5 (aggregated) as fallback          ├─► candidate list with confidence
      ──► registration-number lookup (ltn) if market  ─┘
      ──► if 1 candidate: confirm screen; if 2–3: choose; if 0: guided picker
```

Store every resolution with the raw decoder output, so we can audit wrong matches later.

### 4. Fitment engine = criteria diff + question table

- Fetch articles for (vehicleId, categoryId); keep only those with an in-stock offer.
- Fetch criteria per article (API-GAR-0012/0013). Group articles by their criteria set.
- If one group: recommend. If several: look up each differing criterion in the **question
  table** (DB-managed, editable in admin): `criterion key → question text, answer options,
  picture, which answer maps to which criterion value`.
- Criteria with no question entry → mark order for human fit check instead of asking.
- Recommendation ranking: in stock, brand tier (hand-set: premium/standard/budget), price,
  vendor delivery time.

### 5. Offers and vendor mapping

- Vendor file import per vendor with a per-vendor column mapping (brand, article number, EAN,
  price, qty, lead time). Normalise article numbers (strip spaces, dashes, case).
- Match to catalog via `search-by-articles-no-supplier-id` (brand + number) first, EAN via
  cross-number lookup second. Store `vendor_sku ↔ articleId` once matched; unmatched lines go
  to an admin list.
- Multi-vendor from day one in the schema: `offer(vendor_id, article_id, price, qty, lead_days,
  updated_at)`. Single vendor at launch is just one row per article.

### 6. Orders

- Payment provider by market (Stripe where available; otherwise local PSP; cash on delivery as
  an order status, not a payment).
- Order placed → fit-check status (`auto_ok` / `needs_human`) → vendor notification (email +
  CSV at first; API later) → shipped → delivered → return window.
- Return reasons are structured; wrong-fit returns link back to the vehicle, article and the
  questions asked, so the question table improves.

## Calls per order (for quota modelling)

Typical first visit, cache cold: 1 VIN decode + 1 vehicle details + 1 category list + 1 article
list + N criteria calls (N = articles in stock in that category, often 3 to 10) + 1 media call
≈ 8 to 15 calls. Cached repeat vehicle: 3 to 5 calls. Use these to price the plan against
expected orders (doc 01, A6).

## Non-functional

- Logs redact the API key and store VINs hashed except in the resolution audit table (access
  restricted). Keep personal data minimal (phone, address) and know the local privacy law.
- Uptime target modest at launch; the human fallback (WhatsApp) is the real SLA.
- One region, one language, one currency in code paths; do not abstract these yet.

## Build order (phase 2, 8 to 10 weeks for one developer)

1. Catalog adapter + cache + VIN pipeline, with a CLI to run the 50-VIN test again.
2. Vendor import + mapping + admin unmatched list.
3. Storefront: VIN → confirm → category → result (no questions yet; everything "human check").
4. Checkout + order → vendor email.
5. Question table + fitment engine, seeded from phase-1 concierge learnings.
6. Returns flow + reporting.
