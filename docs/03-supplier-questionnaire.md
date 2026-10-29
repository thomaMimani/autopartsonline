# 03 — Vendor (supplier) questionnaire and what we offer them

Use for the first vendor and every one after. Take notes in the right-hand column and then run
`/supplier-readiness-check` on the notes to get a score and a go/no-go.

## How to open the conversation

Do not pitch the platform. Ask about their business first (how they sell today, who their
customers are, what hurts). Then describe what we want in one sentence:

> "We want to sell your parts to ordinary drivers online. Customers find the part by VIN, pay
> us, and you ship it or hand it to our courier. We need your price and stock list in a file."

## A. Their business today

| Question | Why it matters | Notes |
|---|---|---|
| Who are your customers today: garages, retail walk-ins, other shops? | Tells us if retail is new to them | |
| Which brands do you carry, and which categories are your strength? | Must overlap with our launch scope (wear parts) | |
| Which car makes/models do you sell most parts for? | Defines our first 20 models | |
| Do you already sell online anywhere (own site, marketplaces)? | Channel conflict, price expectations | |
| Roughly how many SKUs do you stock, and how many lines ship per day? | Scale and ops maturity | |

## B. Data (go/no-go)

| Question | Why it matters | Notes |
|---|---|---|
| Can you export a price + stock list? Format: Excel, CSV, API, nothing? | Without a file we cannot show stock or price | |
| Does every line have **brand + brand article number** (e.g. BOSCH 0986494xxx)? EAN? | This is the only way to match their stock to the catalog | |
| How often can you send it? Daily is fine, hourly is great, weekly is not | Stale stock = cancelled orders | |
| Is stock quantity real, or just "available / not available"? | Decides what we can promise | |
| Do you have a reference to any catalog (TecDoc article IDs, OE numbers)? | Shortcut for matching | |

## C. Operations

| Question | Why it matters | Notes |
|---|---|---|
| If we send you an order at 14:00, when does it leave? | Delivery promise | |
| Will you ship to the end customer with our branding on the packing slip, or do we collect? | Who owns the box | |
| Delivery area, courier used, shipping cost per parcel | Pricing | |
| What happens when a part is in the file but not on the shelf? | Cancellation handling | |
| Can you handle 5 orders/day now? 50/day in a year? | Capacity | |

## D. Commercial

| Question | Why it matters | Notes |
|---|---|---|
| What is our buying price vs. your retail counter price? (ask for a tier, not a number) | Our gross margin | |
| Payment terms: prepay, weekly settlement, monthly credit? | Cash flow | |
| Returns: do you take back unopened wrong-fit parts? Restocking fee? Within how many days? | Our return promise depends on this | |
| Warranty handling: who does the customer talk to? | Trust | |
| Exclusivity: do you mind us adding other vendors? | Marketplace path | |
| Minimum order value or quantity? | Small retail orders | |

## E. Red flags

- Price list only on paper or "come to the counter".
- No brand article numbers (only internal codes with no mapping).
- "We'll ship when we can."
- Wants exclusivity and a listing fee before any sales.
- Returns refused outright.

## What we offer the vendor

- A new retail channel with zero marketing cost to them.
- We handle the customer: support, payment, fitment questions, returns first line.
- Transparent weekly report: orders, cancellations, returns, reasons.
- No commitment beyond a pilot of 6 to 8 weeks.
- Their counter price stays protected online if they want (we agree a floor).

## After the call

1. Paste the notes into `/supplier-readiness-check`.
2. Record the go/no-go in `02-discussion-prep.md` decision log.
3. If go: request the sample price file and 10 real part numbers to run through the catalog
   matching test (doc 06, phase 0).
