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
| | Market: ___ | | |
| | Launch customer: ___ | | |
| | Model: drop-ship shop (proposed) | | |
| | Category scope: wear parts (proposed) | | |
