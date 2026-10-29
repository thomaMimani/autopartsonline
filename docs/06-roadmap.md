# 06 — Roadmap: phases, gates, and what happens before any code

The rule: **no application code until phase 1 passes its gate.** Each phase ends with a
written go/no-go in the decision log (doc 02).

## Phase 0 — Discovery (2 weeks, cost ≈ one API trial plan)

Goal: know the market, the vendor, and the catalog's real coverage.

| Task | Output | Owner |
|---|---|---|
| Decide market, customer, constraint (doc 02, Q1/Q2/Q6) | Decision log entries | Founder |
| Vendor call using doc 03, then `/supplier-readiness-check` | Score + go/no-go, sample price file | Founder |
| Get API trial, base URL, pricing, licence answers (doc 02, part B) | Notes in `docs/reference/` | Founder / Claude |
| **50-VIN test**: collect 50 real VINs from the market (friends, vendor's invoices, used-car listings). Run each through `vin-check` and `decoder-v5`. Record: single match / multiple / none, and whether the match is right | `docs/reference/vin-test-results.md` with rates per make | Claude (script) + founder (truth check) |
| **Criteria census**: for the top 20 models × launch categories, pull articles and criteria. Count how often in-stock articles differ by criteria, and which criteria | Table: category → % of lookups needing a question → top criteria | Claude |
| **Mapping test**: match 10 to 50 lines of the vendor's price file to catalog articles by brand + number | Match rate | Claude |

**Gate 0:** vendor is a go, VIN single-or-choosable match ≥ 80 percent on the 50 VINs, mapping
rate ≥ 70 percent, licence allows a public shop. Any one failing → fix or stop.

## Phase 1 — Concierge MVP (4 to 6 weeks, almost no code)

Goal: test A1 (demand) and A4 (wrong-fit rate) with real money changing hands.

- One landing page (no-code builder) with the VIN field and a WhatsApp button. Copy: "Send us
  your VIN and what you need. We find the right part and deliver it. Fits or your money back."
- Behind the page: the founder (or one helper) does the lookup by hand against the API console
  or a small CLI script, confirms the fit, quotes, takes payment (bank transfer, payment link,
  or cash on delivery), orders from the vendor, arranges delivery.
- Log every request in one spreadsheet: VIN, what they asked, how many questions we needed to
  ask, what we sold, margin, delivery time, return (yes/no, reason).
- Recruit 40 requests: friends-of-friends, local Facebook/WhatsApp groups, a flyer at two
  independent garages, the vendor's walk-in customers.
- In parallel: 10 Mom-Test interviews with drivers who recently bought a part.

**Gate 1 (the decision rule from doc 01):** ≥ 30 percent of requests paid, wrong-fit returns
< 10 percent, vendor on-time ≥ 80 percent. Also capture: the top 10 criteria questions we had to
ask (this seeds the question table), and the payment method mix.

## Phase 2 — Build the shop (8 to 10 weeks, one developer)

Build order in doc 05. Launch to the same market with the same vendor. Keep the WhatsApp
human path visible. Target: 10 orders/day with human fit-check on flagged orders only.

**Gate 2:** 4 consecutive weeks with wrong-fit returns < 5 percent and margin after returns and
API cost positive per order.

## Phase 3 — Second and third vendor, wider scope

- Add vendors through the same import path; show the best offer per article.
- Expand categories by criteria-census results (easy categories first).
- Consider the marketplace legal form, garage (B2B) accounts, partner-garage installation.

## What we are explicitly not doing yet

Fundraising, multi-country, native apps, parts diagrams, SEO content farms, paid ads at scale.
Each of these is a decision after gate 2, not a default.

## Timeline at a glance

```
Week  1–2   Phase 0  discovery, 50-VIN test, vendor go/no-go        ──► Gate 0
Week  3–8   Phase 1  concierge, 40 real requests, 10 interviews      ──► Gate 1
Week  9–18  Phase 2  build + soft launch                             ──► Gate 2
Week 19+    Phase 3  vendors 2 and 3, scope growth
```
