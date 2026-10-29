# 07 — Stress-test report, run 1 (2026-10-29)

Skills run: `/stress-test-project-plan` on docs 01 and 06; `/stress-test-product-design` on
doc 04. Nothing here is validated with real customers or a live API yet. Every number marked
"estimate" is reasoning, not data.

## Part 1 — Project plan

### Five-line restatement

- Customer: non-mechanic drivers who buy their own parts or buy for their mechanic. Market: **not yet chosen**.
- Problem: they cannot tell which part fits; they fear buying wrong; so they overpay the garage.
- Offer: VIN in, one right part out, delivered, fits-or-money-back.
- Money: drop-ship margin between vendor price and retail (estimate 15 to 35 percent).
- Must be true: drivers self-serve (A1), VIN resolves fitment (A2), a vendor gives data and ships (A3), returns stay low (A4).

### Assumption audit

| ID | Assumption | Type | Confidence / evidence | If wrong |
|---|---|---|---|---|
| A1 | Drivers want to buy parts themselves | Demand | None yet. Strong in "bring your own part" markets, weak in dealer-centric ones | No business; pivot to garages |
| A2 | VIN → one catalog vehicle, ambiguity closable with ≤ 3 questions | Solution | Medium. Catalog VIN coverage is known to be uneven by region; criteria exist on most brake/suspension parts | Core promise breaks; returns explode |
| A3 | Vendor gives machine-readable stock + ships in 1 to 2 days | Supply | None. One vendor, not yet spoken to | No shop; concierge only |
| A4 | Wrong-fit returns < 5 percent long term | Economics | Low. Industry consumer-parts return rates are commonly quoted in the 10 to 20 percent range (estimate, unverified) | Margin gone |
| A5 | Customers pay a new site online | Trust | Market-dependent | Need cash on delivery, raises cancellation |
| A6 | API cost per order small | Economics | Unknown pricing | Could eat 5 to 20 percent of margin at low volume (estimate) |
| A7 | Licence allows public display and caching | Legal | Unknown | Must re-architect or change provider |
| **A8 (new)** | The founder has enough hours and cash to run a 6-week concierge phase personally | Capacity | Not stated in docs (Q6 blank) | Phase 1 never produces 40 requests |
| **A9 (new)** | Returns from drivers are mostly wrong-fit, not "the mechanic said something else" | Economics | Unknown | The question flow cannot fix a returns problem that is really a trust problem |

**Riskiest, confirmed:** A1 × A2. **Challenge:** doc 01 is right to pair them, but A3 is the
cheapest to settle (one phone call) and gates everything else. Do A3 first, in week 1.

### Pre-mortem: five obituaries

1. **"We built a shop for a market where people let the garage buy."** Market chosen by
   convenience, not by the self-buy habit. Warning sign: interviewees say "my mechanic handles
   it" in 7 of 10 interviews. Action now: make Q1/Q2 interviews precede any API spend.
2. **"VIN lookup was right 70 percent of the time, and the 30 percent killed us."** Decoder
   returned plausible wrong variants; drivers confirmed them; pads did not fit. Warning sign:
   50-VIN test shows < 80 percent correct single/choosable match. Action: run the 50-VIN test
   with truth-checked registration documents, not just "did it return something".
3. **"The vendor's stock file was a wish list."** Orders cancelled after payment, trust gone.
   Warning sign: > 10 percent of concierge orders hit "not on shelf". Action: in the pilot,
   call the vendor to confirm each line before taking payment; measure the discrepancy.
4. **"Returns ate the margin."** Fits-or-free-return promised publicly before the question
   flow existed. Warning sign: phase-1 wrong-fit rate > 10 percent. Action: promise it in
   phase 1 only to the 40 concierge customers, where a human checked every fit.
5. **"The founder ran out of evenings."** Concierge phase is a part-time job: lookups,
   WhatsApp, vendor chasing, deliveries. Warning sign: fewer than 10 requests handled by week 3.
   Action: fill Q6 now; if under 10 hours/week, cut phase 1 to 20 requests and 4 weeks.

### Dependency-risk map

| Dependency | They can change | Blast radius | Mitigation |
|---|---|---|---|
| Catalog API (apiprofile) | Price, quota, licence, endpoints, shutdown | Total: no fitment data | Written licence answer before build; cache with full-scope keys; keep an adapter boundary so a TecDoc-style alternative can replace it; store our own question table and vehicle resolutions |
| Single vendor | Price, terms, exclusivity, stock accuracy, stops shipping | Total at launch | Pilot terms in writing; second vendor conversation started in phase 1, not phase 3; schema multi-vendor from day one (already in doc 05) |
| VIN decoder quota (`VIN_API_KEY`) | Separate quota, may be throttled | Lookups fail at peak | 90-day VIN cache; registration-number fallback where market allows; guided picker always available |
| Payment provider | Fees, availability per market | Checkout | Decide per market in Q1; cash on delivery as fallback |
| Courier | Price for heavy parts, reliability | Delivery promise | Price heavy categories (discs, batteries) separately; measure in pilot |
| Consumer law | Return windows, liability | Economics | Check before public promise; the promise must at least match the legal minimum |

### Unit economics (one order, all estimates until phase 0/1 fills them)

| Line | Value | Provenance |
|---|---|---|
| Average order value | 60 | estimate: pads/filters/wipers mix |
| Vendor cost | 45 | estimate: 25 percent gross margin |
| Gross margin | 15 | derived |
| Payment fee | 1.5 | estimate: 2.5 percent |
| Shipping net of customer charge | 2 | estimate |
| Returns: 8 percent × 15 (two-way shipping + handling) | 1.2 | estimate |
| Catalog API cost per order | 0.5 to 2 | unknown; depends on plan and cache hit rate |
| Support / fit check: 10 min × 15/h | 2.5 | estimate; phase 1 is far higher |
| Marketing (CAC) | 5 | estimate, organic + local |
| **Contribution** | **≈ 1 to 3** | thin |

Break-even sentence: under these estimates contribution goes to zero at roughly a 15 percent
returns rate, or at an API cost above ~3 per order, or at a CAC above ~8. The business works
only with margin ≥ 25 percent **and** returns < 10 percent **and** repeat purchases (service
intervals make this plausible). The three numbers to obtain first: vendor margin tier, API plan
price, phase-1 returns rate.

### Timeline and gates

- Gates 0 and 1 have numbers set in advance and a stop/pivot action. Pass.
- Gate 2 ("margin positive after returns and API cost") is falsifiable only if the unit model
  above is maintained with real numbers from week 9. Add: owner and weekly update.
- Hidden work not in the roadmap: building the criteria-to-question table (doc 04 step 3) is
  ongoing editorial work, estimate 2 to 4 hours per category at the start; customer support
  during phase 2 has no owner; vendor file cleaning each day in phase 2 has no owner.
- Phase 2 "8 to 10 weeks, one developer" assumes a developer exists (Q6 blank).

### Competition

In Western Europe the incumbents already do VIN/registration lookup, next-day delivery and
free returns; our only defensible angles would be local stock speed and human fit help, which
they can partially copy. Outside Western Europe the comparison set is the parts counter and
the mechanic; the differentiation is convenience and price transparency, which is durable as
long as fit accuracy holds. This strengthens the recommendation to choose a market where the
incumbents are weak and the self-buy habit is strong.

### Verdict: **Fix first**, then go to phase 0

Changes to make before phase 0 starts:

1. Fill Q1, Q2, Q6 (market, customer, constraint). Without them the plan is a template.
2. Move the vendor call to week 1, day 1; start a second vendor conversation during phase 1.
3. Get the licence and pricing answers from the API provider in writing before the 50-VIN test.
4. Keep the fits-or-free-return promise private to concierge customers until gate 1.

Assumption to test first: **A3 (vendor)**, because it is settled by one call. Then A1 × A2
through the concierge phase with the pass bar already in doc 01.

## Part 2 — Product design (doc 04)

### Persona

A 38-year-old office worker with a 9-year-old diesel hatchback. Knows where the registration
document is, not where the VIN is on the car. Mechanic said "pads, front". Shopping on a phone
in the evening, afraid of ordering wrong, considering just letting the mechanic buy them.

### Journey walk-through

| Step | Sees | Thinks | Result |
|---|---|---|---|
| 0 Landing | One VIN field with a picture of the document | "OK, it's that long number." Types it, mistakes O for 0 | friction (validation must catch it) |
| 1 Confirm | "Golf VII 1.6 TDI 105 hp 2013–2017" + photo | "That's it. But mine's a 2015, is that fine?" | smooth if range wording is clear |
| 1b Two candidates | Two cards: 105 hp vs 110 hp | "No idea. Document field P.2 says 77 kW." Needs a kW-to-hp hint | friction; hint fixes it |
| 2 Category | Grid: Brakes | "Brakes. Pads? Discs? He said pads." Needs sub-choice in plain words | friction |
| 3 Question | "Front or rear?" with picture | "Front, he said." | smooth |
| 3b Criterion | Brake system ATE vs TRW: "we'll check for you" | "Fine, as long as someone checks." | smooth, but depends on a human |
| 4 Result | One recommended pad set + add-on "wear sensor?" | "Do I need the sensor? What is it?" | friction; one sentence of explanation needed |
| 5 Checkout | Guest, phone number, pay | "Never heard of this shop." Looks for a phone number and a return policy | breaks without trust strip |
| 6 After | Human check, SMS | "They actually checked." | smooth |

### Findings

| ID | Sev | Scenario | Where it breaks | Impact | Smallest mitigation | Evidence needed |
|---|---|---|---|---|---|---|
| F1 | Blocker | Decoder returns one confident but wrong variant (e.g. facelift vs pre-facelift); customer taps "yes" | Step 1, single-candidate confirm | Wrong part shipped under a guarantee; return cost on us | Show 2 to 3 distinguishing facts the driver can verify on the document (kW, fuel, first registration year), not just a name; "not sure" button leads to a 2-question check | 50-VIN test: wrong-confident rate |
| F2 | Blocker | Criteria differ by chassis number range or build month; no question possible; human check queue has no owner | Step 3 "human check" | Orders stall or ship unchecked | Name the owner and an SLA (same day before 17:00) in the design; in phase 2 show "ships tomorrow after our check" explicitly | Criteria census: share of lookups hitting untranslatable criteria |
| F3 | Major | VIN decode fails (import, older car) and the guided picker asks for engine code | Step 0 fallback | Abandon | Picker order: make → model → year → fuel → power in kW/hp with document hints; never engine code as a required field | Picker completion rate |
| F4 | Major | Customer needs two articles (pads + sensor, or left + right) and buys one | Step 4 add-on | Second order or return; angry customer | Bundle rules per category in the question table ("pads for this car usually need a sensor: add it?") with one-line explanation | Return reasons "incomplete" |
| F5 | Major | Vendor has no stock for the category; page shows nothing | Step 2 filter by stock | Dead end, feels broken | Show the category with "ask us" fallback that captures VIN + need for the concierge path | Share of vehicle+category lookups with zero offers |
| F6 | Major | First-time buyer sees no trust signals at checkout | Step 5 | Abandon | Address, phone, return policy and payment logos on checkout and landing; phase-1 testimonials later | Checkout drop-off |
| F7 | Major | "I don't know" is not an answer option on questions | Step 3 | Guess → wrong fit | Every question has "not sure, check for me" routing to the human queue | Share of "not sure" answers |
| F8 | Minor | VIN typo (O/0, I/1) | Step 0 | Decode fails | Auto-correct O→0, I→1, Q→0 with a notice; check digit validation where the market uses it | Typo rate |
| F9 | Minor | Category words do not match the customer's words | Step 2 | Search fails | Synonym list per category seeded from phase-1 WhatsApp transcripts | Zero-result searches |
| F10 | Minor | Delivery cost and time not on the result card | Step 4 | Surprise at checkout | Show both on the card | — |
| F11 | Minor | Phone VIN entry is painful | Step 0 | Friction | Paste support, uppercase forcing, later photo OCR | — |

### Data-dependency list

- Decoder coverage per make in our market → F1, F3. Unknown until the 50-VIN test.
- Criteria completeness and whether criteria are consistent across brands → F2. Unknown until
  the criteria census.
- Vendor stock accuracy → F5. Unknown until the vendor call and pilot.
- Image availability per article → result card quality. Unknown.
- Model photo availability (passenger cars only per the API) → step 1 confirmation quality.

### Cost of the promise

If wrong-fit happens on 10 percent of orders and each costs ~15 in two-way shipping plus
handling (estimate), the promise costs ~1.5 per order, i.e. about 10 percent of the estimated
gross margin. At 20 percent wrong-fit it costs ~3 per order and contribution is gone. The
design has a feedback loop (return reasons → question table) which is the right mechanism;
it needs an owner and a weekly ritual to be real.

### Verdict: **Fix first**

Top three changes:

1. Make the vehicle confirmation verifiable (F1): facts the driver can check on the document,
   plus a "not sure" path.
2. Give the human check an owner and an SLA, and show it to the customer (F2).
3. Add "not sure, check for me" to every question and bundle rules per category (F7, F4).

## Accepted mitigations (to be confirmed in the decision meeting)

None yet. Review in the meeting (doc 02, agenda item 3) and record accept/reject here.
