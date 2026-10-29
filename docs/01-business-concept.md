# 01 — Business concept

## One-liner

A car-parts shop where an ordinary driver types their VIN and gets the right part, delivered,
without needing to know what a "brake caliper for ATE system, front left" is.

## The problem, in the customer's words

> "The mechanic said I need new brake pads. I don't know which ones fit my car. The shops
> online ask me for engine codes and show me 40 options. I'm afraid of buying the wrong one and
> losing a week and the money. So I let the mechanic buy it and pay whatever he says."

Who has this problem: drivers who own a car 3 to 15 years old, do not service it at a dealer,
and either (a) fix small things themselves (wipers, bulbs, filters, pads) or (b) buy the part
and hand it to an independent mechanic. In many markets the "bring your own part" habit is
strong because labour is cheap and mechanics mark parts up heavily. **Which market we are in
decides how strong this problem is. This is open question #1 in doc 02.**

How they cope today:
- Let the mechanic source it (pays margin, no transparency).
- Walk into a parts counter with the registration document.
- Try a big online shop (Autodoc, kfzteile24, Oscaro, Amazon, local equivalents), get confused by
  variants, sometimes buy wrong.
- Ask in WhatsApp / Facebook groups.

## Why now / why us

- Catalog data that used to require a TecDoc licence and a data team is now a REST API with a
  VIN endpoint (AutoPartsAPI v2). The hard "which part fits" data is rentable.
- We can start with one vendor's stock and no inventory of our own (drop-ship).
- The big players optimise for people who already know what they want. A VIN-first, question-led
  flow for people who do not is a real UX gap, especially in smaller or non-English markets.

## What we sell and how we make money

Recommended starting model (see doc 02 for the alternatives):

- **Drop-ship shop, we are the merchant of record.** Customer pays us, we order from the vendor,
  vendor ships (or we pick up and ship). We keep the margin between vendor price and retail.
- Typical gross margin on aftermarket parts online: 15 to 35 percent depending on category and
  vendor terms. Treat this as an assumption to confirm with the vendor, not a fact.
- Later: more vendors behind the same catalog (becomes a marketplace in substance, not yet in
  legal form), service bundles with partner garages, B2B accounts for small garages.

## Who the customer is NOT (for now)

- Professional mechanics and garages. They already have B2B portals and know part numbers.
  They are a possible phase-3 segment, not the launch segment.
- Enthusiasts hunting performance or rare parts.

## Assumption stack (ordered by how fatal it is if wrong)

| # | Assumption | Type | Confidence today | How we find out |
|---|---|---|---|---|
| A1 | Enough non-mechanic drivers in our market want to buy parts themselves (or for their mechanic) rather than let the garage source them | Demand | Unknown, market-dependent | 10 customer interviews + concierge MVP (doc 06, phase 1) |
| A2 | VIN (or registration number) resolves to a single catalog vehicle often enough, and the remaining ambiguity can be closed with 1 to 3 plain-language questions | Solution | Medium. Catalog data has known gaps; fitment criteria exist on many parts | API trial on 50 real VINs from our market; measure single-match rate and criteria rate per category |
| A3 | A vendor will give us a machine-readable price/stock list (or at least a daily file) with brand + article number, and ship within 1 to 2 days | Supply | Unknown, one vendor not yet spoken to | Supplier conversation (doc 03) |
| A4 | Wrong-fit returns can be kept under ~5 percent of orders with the question flow plus a "fits-or-free-return" promise | Economics | Low until measured | Concierge MVP tracks every return reason |
| A5 | Customers trust a new shop enough to pay online before delivery | Trust | Market-dependent (cash on delivery may be needed) | Concierge MVP conversion, payment method mix |
| A6 | API cost per order (VIN decode quota + article calls) stays under a small fraction of gross margin | Economics | Medium, pricing not yet seen | Get plan pricing; model calls per order (doc 05) |
| A7 | Catalog licence allows us to cache and display the data in a public shop | Legal | **Partly answered 2026-10-29:** display in a shop is allowed on paid plans; storing a fitment index needs MEGA or higher; images and SEO-indexed pages need separate confirmation. See `reference/autopartsapi-terms-analysis.md` | Send the drafted questions to the provider |

**Riskiest assumption: A1 combined with A2.** If drivers will not self-serve, there is no business.
If VIN-in does not give one-part-out reliably, the core promise breaks and we are just another
confusing shop. Both can be tested in 4 to 6 weeks for almost no money (doc 06, phase 0 and 1).

## Decision rule (set before testing)

After the concierge phase (target 40 real requests):

- **Build** if at least 30 percent of requests convert to a paid order, and wrong-fit returns are
  under 10 percent, and at least one vendor has delivered on time for 80 percent of orders.
- **Pivot the segment** (to small garages, or to a "find the part, buy from the garage" lead model)
  if drivers ask but will not pay, or if the garage ends up doing the buying anyway.
- **Kill or park** if we cannot get 40 requests in 6 weeks with a modest push. Reachability is the
  problem then, not the product.
