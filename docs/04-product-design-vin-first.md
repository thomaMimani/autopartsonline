# 04 — Product design: the VIN-first flow for a non-mechanic

## Design principles

1. **Never ask the customer something the VIN can answer.** Make, model, year, engine come from
   the VIN. If the VIN fails, fall back to registration number (where supported), then to a
   guided picker (make → model → year → fuel → power), in that order.
2. **Ask at most three plain-language questions, with pictures.** "Front or rear?" "Is your
   brake disc about this size (hand span) or bigger?" "Does your key look like this?" Never
   show "PR-number 1LJ" or "from chassis no. WBA…". Translate every fitment criterion into a
   question a driver can answer by looking at the car, or decide it for them.
3. **One recommended part, then alternatives.** Show one "best choice" (in stock, good brand,
   fair price), then "cheaper" and "premium". Forty results is a failure of the product, not a
   feature.
4. **The promise carries the risk.** "Fits your car or your money back, return shipping on us."
   This is the only thing that makes a nervous first-time buyer click. The question flow exists
   to make that promise affordable.
5. **A human is one tap away.** WhatsApp / chat button on every page with the vehicle already
   attached. In phase 1 this *is* the product.
6. **Mobile first, slow network tolerant.** Most VIN lookups happen in a parking lot or a
   garage forecourt.

## The journey

### Step 0 — Landing

- One field: "Enter your VIN (17 characters, on your registration document or windscreen)".
  Below it: "Don't have it? Use your registration number" (if market supports it) and "Pick
  your car manually".
- A picture showing where the VIN is on the document and the windscreen.
- Trust strip: fits-or-free-return, delivery time, local phone number.

### Step 1 — Vehicle confirmation

- We show: "Volkswagen Golf VII 1.6 TDI 105 hp, 2013 to 2017, diesel." Plus a model photo
  (the API offers one for passenger cars).
- Buttons: "Yes, that's my car" / "No, not quite" (goes to the guided picker, pre-filled with
  what we are sure of).
- If the decoder returns several candidate `vehicleId`s (common: same model, two engine
  variants), show them as 2 to 3 cards with the differences in plain words (power, fuel,
  gearbox) and let the customer pick. Never ask for an engine code; show it only as a
  "check on your registration document, field P.5" hint.
- The vehicle is remembered (cookie + account later). "My garage" lets a user keep several cars.

### Step 2 — What do you need?

- A short grid of categories with icons, in driver language: Brakes, Filters & oil service,
  Wipers, Lights & bulbs, Battery, Belts, Spark plugs, Suspension. Search box with synonyms
  ("brake blocks", "pastillas", local slang).
- Also a "I have a part number / the old part in my hand" path for the few who do.
- Only categories with articles for *this* vehicle are shown (category variant 4 from the API,
  filtered by the vendor's stock).

### Step 3 — Narrowing questions (only if needed)

For the chosen category and vehicle, we fetch the article list and the fitment criteria. Then:

- If all in-stock articles share the same criteria: skip questions.
- Otherwise, generate questions from the criteria that actually differ, using a hand-maintained
  translation table (criterion → question text + picture). Examples:
  - Front axle / rear axle → "Front wheels or rear wheels?"
  - Brake disc diameter 280 vs 312 → "Measure the disc or check: do you have 15-inch or 17-inch
    wheels?" with a picture.
  - Brake system ATE / TRW → "We'll check this for you" (we resolve via VIN-specific data where
    available, otherwise we ship the one matching most vehicles of that variant and flag it for
    a human check before dispatch).
- Criteria we cannot translate go to a **human check** queue instead of the customer. The
  customer sees "We'll confirm the exact fit before shipping (same day)".

### Step 4 — Result

- One recommended article card: brand, photo, price, "in stock, ships tomorrow", "fits your
  Golf VII 1.6 TDI". Then "cheaper option" and "premium option" cards.
- A "what's included" line (pads only vs pads + wear sensor) in plain words, with an add-on
  prompt: "Most people also replace the wear sensor. Add for X?"
- Hidden by default, one tap to expand: OE numbers, criteria, brand article number. Mechanics
  will use this; drivers will ignore it.

### Step 5 — Checkout

- Guest checkout. Phone number required (delivery and fitment questions). Email optional.
- Payment: whatever the market trusts (card, local wallet, cash on delivery if needed).
- Order confirmation repeats the vehicle and the fits-or-free-return promise.

### Step 6 — After the order

- Human fit check on flagged orders before dispatch (phase 1: on all orders).
- Delivery tracking by SMS/WhatsApp.
- Return flow: one form, reason captured (wrong fit / changed mind / damaged / other). Wrong-fit
  reasons feed back into the question table.

## What we deliberately do not build at launch

Accounts (beyond "my garage" via cookie), wishlists, reviews, loyalty, multi-currency,
multi-language, B2B pricing, parts diagrams with hotspots (nice later), returns portal
(email + phone is fine), marketplace vendor dashboards.

## Where this design is weakest (to be stress-tested)

- The criteria-to-question translation table is manual work and grows with every category.
- VIN decoders can return the wrong variant with confidence. We need a visible "not my car"
  escape on every page.
- Drivers often do not know front vs rear, or which side. Pictures help; a human helps more.
- "Fits or free return" bleeds money if the question flow is weak. Phase 1 measures this
  before we promise it publicly.
- Some parts need two articles (left and right, or pads plus sensor); the flow must bundle,
  not assume the customer knows.
