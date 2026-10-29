# 02 — Discussion prep: questions to settle, with recommendations

Purpose: walk into the next conversation (with yourself, a co-founder, or the supplier) with
every open question named, a recommendation on each, and a place to log the decision.
Questions are ordered so that each answer narrows the next.

## Part A — Questions only the founder can answer

### Q1. Which market (country) do we launch in?

Everything downstream depends on this: VIN decoding quality, whether registration-number
lookup is available (the API supports German KBA, Dutch Kenteken, French Typemine), payment
habits, competition, language, and the vendor's delivery area.

- If the market is Germany / Netherlands / France: registration-number lookup beats VIN for
  catalog matching and customers know it. Competition (Autodoc, kfzteile24, Oscaro) is brutal.
  Differentiation must be local stock + same/next-day + fitment help.
- If the market is outside Western Europe (Eastern Europe, Balkans, Middle East, Africa, LatAm):
  VIN is the practical key, catalog coverage must be checked per popular model, the "bring your
  own part" habit is usually stronger, and competition is thinner. Cash on delivery may matter.

**Recommendation:** pick the one country where the first vendor physically is, and where you
can talk to customers face to face. One country, one language, one currency for the first year.

### Q2. Who is the launch customer, precisely?

Options: (a) DIY driver who installs simple parts, (b) driver who buys and brings the part to a
mechanic, (c) small independent garage.

**Recommendation:** (b) first, (a) second. They are the same person on different days, and the
flow is identical. Do not target garages at launch: they need B2B pricing, credit terms, and
part-number search, which is a different product.

### Q3. Shop or marketplace?

| | Drop-ship shop (we sell) | Marketplace (vendors sell) |
|---|---|---|
| Who the customer pays | Us | Vendor, via us |
| Who handles returns/warranty | Us (then back to vendor) | Vendor |
| Legal/admin load | Invoice, VAT, consumer law on us | Payment splitting, vendor contracts, KYC |
| Vendor count needed to make sense | 1 | 3+ |
| Control of customer experience | High | Low |

**Recommendation:** drop-ship shop now. With one vendor, "marketplace" is a word, not a
business. Design the data model so a second and third vendor plug in behind the same catalog
(doc 05), and revisit the legal form when vendor 3 is live.

### Q4. Starting category scope?

**Recommendation:** start narrow where fitment is most resolvable and demand is frequent:
wear parts (brake pads, discs, filters: oil/air/cabin/fuel, wiper blades, spark plugs, bulbs,
batteries, belts). Exclude body panels, electronics, engine internals at launch. Narrow scope
makes the question flow and the returns promise credible.

### Q5. Which car makes/models first?

**Recommendation:** the 20 most common models in the market (registration statistics or the
vendor's own best-sellers). Validate catalog coverage for exactly those before promising
anything. A shop that says "we cover these 20 models perfectly" beats one that covers
everything badly.

### Q6. What is the founder's constraint?

Time per week, cash available for the first 6 months, and whether there is a developer.
This sets the box for the MVP (doc 06). Write it down here:

- Hours/week: ___
- Cash for 6 months: ___
- Who builds: ___

## Part B — Questions to settle with the catalog API provider

Ask in writing and keep the answers (they belong in `docs/reference/`).

1. Base URL and plan pricing: monthly quota, requests per second, cost of VIN calls.
2. Does the licence allow caching responses and showing catalog data (names, images, fitment)
   publicly in a shop? Are there attribution requirements?
3. Which VIN decoder (`vin-check`, `decoder-v2`, `decoder-v5`) returns a catalog `vehicleId`,
   and what is its coverage for our market's common models?
4. Country filter: which `countryFilterId` to use for our market, and what changes with it.
5. Pagination and max result sizes for article lists.
6. Sample responses for: VIN decode, vehicle details, article list, article criteria, media.
7. Sandbox or trial account to run the 50-VIN test (doc 06, phase 0).

## Part C — Questions to settle with the vendor

See `03-supplier-questionnaire.md`. The three that decide go/no-go:

1. Can you give us a price + stock file with brand and article number, at least daily?
2. Will you ship to the end customer (or hand over to our courier) within 1 to 2 working days?
3. What is our buying price vs. your counter price, and do you accept returns of unopened
   wrong-fit parts?

## Part D — Proposed agenda for the first internal decision meeting (60 min)

1. (10 min) Confirm market, customer, and constraint (Q1, Q2, Q6).
2. (10 min) Shop vs marketplace, category scope (Q3, Q4, Q5).
3. (15 min) Walk the stress-test findings (doc 07) and accept or reject each mitigation.
4. (15 min) Agree the phase-0 and phase-1 plan and the decision rule (doc 01, doc 06).
5. (10 min) Assign: who contacts the API provider, who runs the vendor call, who recruits
   the first 10 interviewees.

## Decision log

| Date | Decision | Why | Who |
|---|---|---|---|
| 2026-10-29 | Use the word **vendor** for stock partners and **brand** for the catalog's `supplier` | Avoid confusion with the API's naming | Claude (proposal) |
| 2026-10-29 | Market: **Albania** | Founder's market; used-import fleet; VIN is the key | Founder |
| 2026-10-29 | Launch customer: drivers who buy for themselves or their mechanic; technician behind the shop | | Founder |
| 2026-10-29 | Model: **we are the merchant**, drop-ship from vendors, not a marketplace | | Founder |
| 2026-10-29 | Founder is the developer; MVP now, about 6 weeks | Capacity | Founder |
| 2026-10-29 | Three panels: admin/helpdesk, customer, vendor | | Founder |
| 2026-10-29 | Vendor links by their own API if they have one, else CSV/XLSX upload | | Founder |
| 2026-10-29 | Catalog linking happens **on upload** (fitment index in our DB), not at search time. See doc 08 | Speed, quota, resilience | Claude (proposal) |
| 2026-10-29 | Transport handled later; cash on delivery at launch | | Founder |
| 2026-10-29 | Stack: one Laravel app on Postgres; storefront Inertia React with SSR; admin and vendor panels in Filament (superseded: no Filament, see below); database queue. Next.js deferred | Solo dev, one deploy, panels are CRUD | Founder |
| 2026-10-29 | Matches stored in a shared `part_matches` table (brand + normalised number → article), reused across vendors | Zero API calls for parts already matched | Claude (proposal) |
| 2026-10-29 | API terms read: fitment index and VIN cache need MEGA plan or higher; API-derived data lives in a separate `catalog` schema; no catalog images and no catalog-derived content on indexed pages until confirmed in writing | Terms §5, §6, §7 | Claude (proposal) |
| 2026-10-29 | **MEGA plan in production.** Link-on-upload index design stays. Before MEGA is active, nothing from the API is stored: explore with curl, develop against a fake catalog adapter with hand-written fixtures | Terms §5 apply to every environment, not only production | Founder |
| 2026-10-29 | **No Filament.** Admin and vendor panels are Inertia React, like the storefront | Founder's choice | Founder |
| 2026-10-29 | Build step by step, each step working and tested (doc 10) | | Founder |
| | Category scope: wear parts (proposed) | | |
