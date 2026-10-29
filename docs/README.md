# Planning package — read in this order

| # | Document | Purpose |
|---|---|---|
| 01 | [Business concept](01-business-concept.md) | What we are building, for whom, why it could work, and the assumption stack |
| 02 | [Discussion prep & decisions](02-discussion-prep.md) | The questions we must answer, my recommendation on each, meeting agenda, decision log |
| 03 | [Supplier questionnaire](03-supplier-questionnaire.md) | What to ask the first supplier (and the next ones), and what we offer them |
| 04 | [Product design: VIN-first flow](04-product-design-vin-first.md) | The customer journey for a non-mechanic, screen by screen, and how we handle "it almost fits" |
| 05 | [Technical architecture](05-technical-architecture.md) | How the system wraps the catalog API, vendor data, and checkout |
| 06 | [Roadmap](06-roadmap.md) | Phases, gates, what we do before writing code |
| 07 | [Stress-test report](07-stress-test-report.md) | First run of the stress-test skills against 01, 04 and 06. Honest findings |
| 08 | [MVP architecture, Albania](08-mvp-architecture-albania.md) | The build plan: link-on-upload fitment index, schema, panels, Albanian vocabulary and SEO, 6-week order |
| 09 | [Core flows](09-core-flows.md) | Diagrams: system, vendor import, matching ladder, fitment, VIN, search to order, order lifecycle, data freshness, tables |
| 10 | [Build steps](10-build-steps.md) | The MVP in six working steps, what each delivers, and status |
| ref | [AutoPartsAPI v2 guide](reference/autopartsapi_v2_practical_guide.md) | The catalog API reference we build on |
| ref | [AutoPartsAPI terms analysis](reference/autopartsapi-terms-analysis.md) | What the provider's terms allow, design changes, fallback, questions to send |

## Vocabulary

- **Brand** — part manufacturer (Bosch, Brembo, Febi). The catalog API calls this `supplier`.
- **Vendor** — a distributor / wholesaler who holds stock and ships. This is what the founder means by "supplier". We use **vendor** everywhere to avoid clashing with the API's word.
- **Catalog vehicle (`vehicleId`)** — one engine/body variant of a model. Fitment is decided at this level, never at model level.
- **Article (`articleId`)** — one catalog part from one brand. **Offer** — one vendor's price and stock for one article.
- **Fitment criteria** — the extra conditions on an article ("from chassis no. X", "for brake system ATE", "left/right"). These are where VIN-only lookups fail.
- **Concierge MVP** — we do the lookup by hand for real customers before writing code.

## How to re-run the stress tests

```
/stress-test-product-design docs/04-product-design-vin-first.md
/stress-test-project-plan docs/01-business-concept.md docs/06-roadmap.md
/supplier-readiness-check <paste the notes from the supplier call>
```
