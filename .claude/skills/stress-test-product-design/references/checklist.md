# Stress-test product design — checklists

## Persona defaults for this project

- Owns a 3 to 15 year old car, does not service it at a dealer.
- Can find the VIN on the registration document if shown where. Cannot read an engine code
  with confidence. May not know front from rear pads matter.
- Buys on a phone, often in a hurry, sometimes standing next to the mechanic.
- Fear: paying for the wrong part and losing a week. Second fear: being scammed by a new site.
- Has an alternative: let the mechanic buy it. That alternative costs more but feels safe.

## Failure modes to check

### Vehicle identification
- VIN typed wrong (O vs 0, I vs 1). Is there validation and a friendly fix?
- VIN decodes to nothing. What is the fallback and how many steps is it?
- VIN decodes to the wrong vehicle with confidence. Can the customer notice and escape?
- VIN decodes to multiple candidates. Are the differences explained in words a driver knows?
- Imported, modified, or re-engined vehicles. Does the design admit when it cannot be sure?
- Registration-number lookup used where the market does not support it, or vice versa.

### Finding the part
- Customer uses a word the category grid does not (slang, other language, "the rubber thing").
- Customer needs two parts and does not know it (pads + sensor, left + right, filter + oil).
- Category exists in the catalog but vendor has no stock. Empty page or helpful page?
- Customer has the old part in hand with a number. Is there a path?

### Fitment criteria
- Criteria that differ between articles but have no plain-language question (brake system
  brand, chassis-number ranges, build month, option codes).
- Question asked with no picture or with a picture the persona cannot match to their car.
- Customer answers "I don't know". Is that an option, and what happens?
- Criteria answered but the recommendation still ambiguous. Who decides?
- Human check promised but the queue has no owner or SLA.

### Result and trust
- Too many options shown; no clear default.
- Price shown without delivery cost and time.
- No visible return promise, or a promise the economics cannot support.
- No human contact path, or one that loses the vehicle context.
- First-time-buyer trust signals absent (address, phone, reviews, payment logos).

### Checkout and after
- Forced account creation.
- Payment methods do not match the market.
- Order confirmation does not repeat the vehicle and part, so wrong orders are not caught early.
- Return reasons not captured in a structured way, so the flow cannot learn.
- Vendor out of stock after order; no cancellation path.

### Mobile and environment
- Flow needs a keyboard-heavy input on a phone (17-character VIN): is paste / photo / camera
  OCR considered?
- Slow network: are images and catalog calls blocking the first screen?

## Data-dependency prompts

For each: what does the design assume, what happens when the assumption fails, how often will
it fail (estimate with reasoning), and how will we know.

- Decoder coverage for our market's common models.
- Catalog article completeness per category.
- Criteria completeness and consistency across brands.
- Vendor file freshness and accuracy.
- Mapping rate vendor SKU → catalog article.
- Image availability per article.

## Severity guide

- Blocker: the core promise ("VIN in, right part out") fails for a common case, or the
  customer loses money/trust, or the business loses money on every such order.
- Major: a meaningful share of customers (think > 10 percent) abandon or get a wrong outcome.
- Minor: friction with an easy workaround.
