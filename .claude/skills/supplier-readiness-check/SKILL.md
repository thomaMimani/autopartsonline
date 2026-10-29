---
name: supplier-readiness-check
description: Score a prospective vendor (parts distributor / supplier) for this shop from call notes or their price file. Use when the user says "check this supplier", "is this vendor usable", "score the supplier call", or pastes notes from a vendor conversation. Produces a scorecard on data, operations, commercial terms and risk, a go/no-go/pilot recommendation, and the exact next asks to send the vendor.
---

# Supplier (vendor) readiness check

You evaluate whether a parts distributor can be the stock partner behind a VIN-first retail
shop. The shop needs machine-readable stock and prices, reliable shipping, workable margin,
and a returns policy that makes a "fits or money back" promise affordable.

## Inputs

- `$ARGUMENTS`: call notes, a questionnaire filled from `docs/03-supplier-questionnaire.md`,
  or a path to a sample price file. If a price file is given, inspect its columns and a sample
  of rows before scoring.

## Scorecard

Score each area 0 to 3 (0 = blocker, 1 = weak, 2 = workable, 3 = strong). Quote the note or
file evidence for each score; "not discussed" scores 1 and goes on the follow-up list.

| Area | What a 3 looks like | What a 0 looks like |
|---|---|---|
| Data: identifiers | Every line has brand + brand article number, ideally EAN | Internal codes only, no mapping |
| Data: freshness | Daily or better, automated (API, FTP, email file) | Manual on request |
| Data: stock truth | Real quantities, matches shelf | "Available" that often is not |
| Category / model fit | Strong in wear parts for our top 20 models | Strength elsewhere (body, trucks) |
| Shipping | Same/next-day dispatch to end customer, our packing slip, tracked | "When we can", collection only |
| Capacity | Handles 50 lines/day without strain | Struggles at 5 |
| Margin | Buying price leaves ≥ 20 percent gross after shipping | ≤ 10 percent |
| Payment terms | Weekly or monthly settlement | Prepay every order |
| Returns | Unopened wrong-fit returns accepted ≥ 14 days, no or small fee | Refused |
| Warranty handling | Clear process, they handle brand claims | "Not our problem" |
| Exclusivity / channel conflict | None, comfortable with other vendors later | Demands exclusivity or listing fees |
| Relationship | Responsive, one named contact | Hard to reach |

## Decision

- Any 0 in Data: identifiers, Shipping, or Returns → **No-go** until fixed (say exactly what fix).
- Total ≥ 26 and no 0s → **Go** for pilot.
- 18 to 25 → **Pilot with conditions**: list the conditions.
- < 18 → **No-go**, say which three improvements would change it.

## Output

1. Scorecard table with evidence column.
2. Decision with reasons.
3. Follow-up asks for the vendor, as a short message the founder can send (numbered, specific:
   "sample price file with brand and article number columns", "10 real part numbers",
   "confirm returns window").
4. Risks to log in the dependency map (exclusivity, single source, price changes).
5. Offer to add the decision to the log in `docs/02-discussion-prep.md`.

## Constraints

- Score from evidence in the notes; do not assume goodwill.
- "Not discussed" is a gap, not a pass.
- Keep the vendor message polite, short, and free of jargon.
