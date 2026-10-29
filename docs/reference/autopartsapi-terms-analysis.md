# AutoPartsAPI terms — what they mean for us

Source: AutoPartsAPI "Terms & Usage", last updated 2026-10-26, pasted by the founder on
2026-10-29. Operator: Xolo Go OÜ - API Profile, Estonia. Contact: support@apiprofile.com.
This is a summary with short quotes, not a copy. Re-read the live page before signing up and
whenever the provider announces changes.

## What the terms say, by section

| § | Rule | Effect on our design |
|---|---|---|
| 2, 3 | The database is independently compiled from EPCs and other sources. The subscription is **not** a licence from manufacturers or catalog publishers | Rights risk sits with us, see §10 |
| 4 | Paid plans may be used in online stores. We may show vehicle info, article numbers, OE numbers, criteria, cross-references and fitment, and combine them with our own SKUs, prices and stock. FREE plan is for evaluation only | Our product page design (vendor data next to catalog reference) is explicitly allowed |
| 5 | **Caching and storage only on MEGA and higher.** This covers raw responses, extracted fields, vehicle IDs, OE numbers, cross-references and "vehicle-to-part relationships". On lower plans nothing may be retained, even partially | **Our link-on-upload fitment index and the 90-day VIN cache require MEGA or higher** |
| 5 | On a qualifying plan an "internal fitment or parts-search index" is allowed, but not "downloading or reconstructing the complete catalog" | Our article-centric index (only parts our vendors stock) fits. A vehicle-centric crawl would not |
| 5 | After cancellation or downgrade below MEGA, stop using and **delete retained API content within 30 days** | API-derived data must be isolated so it can be purged in one step |
| 6 | Images, drawings, PDFs and logos may have separate rights. Neither the subscription nor MEGA grants a licence to host or publish them | **No catalog images on the site** until the provider confirms which media we may use |
| 7 | No blanket rights clearance for Google Shopping, ad feeds, or **search-engine-indexed product pages**. Persistent indexes also need a storage plan | Our SEO pages need written confirmation before they show catalog-derived content |
| 8 | No reselling, no data exports, no substitute API, no full replication | No impact, we do none of this |
| 9 | No guaranteed coverage or accuracy. Fitment, VIN and cross-references are reference information to verify | Matches our technician fit check. Show a short notice on product pages |
| 10 | No IP indemnity. Rights holders can complain; the provider may order removal from retained copies | We need a removal path and a plan B data source |

## Changes to the architecture (doc 08, doc 09)

1. **Plan requirement.** MEGA or higher is a precondition of the link-on-upload design. Check
   the price on the pricing page and put it in the unit model (doc 01, A6). If MEGA is not
   affordable at launch, use the fallback below.

2. **Separate API-derived data into its own Postgres schema, `catalog`.** Tables: `articles`,
   `article_vehicles`, `vehicles`, `vin_lookups`, catalog `categories`, the catalog brand IDs,
   and `part_matches.article_id`. Our own data stays in `public`: vendors, vendor items, brand
   aliases (our names), Albanian vocabulary, customers, orders, the technician question log.
   Purging after a downgrade is then `DROP SCHEMA catalog CASCADE` plus nulling a few foreign
   keys. Add a `source_fetched_at` column everywhere in `catalog` for refresh and removal.

3. **Removal handling.** Admin action: "remove article / vehicle from catalog data" that
   deletes the rows and hides affected offers. Needed for §10 notices.

4. **Images.** At launch show the vendor's own photos, our own photos, or a category icon.
   Do not hot-link or host catalog media until the provider answers in writing.

5. **SEO pages.** Build indexed pages from our own data: Albanian category names, the vendor's
   product names, brand plus part number, price and stock. Until the provider confirms in
   writing, keep catalog-derived fitment lists, OE lists and criteria **off indexed pages**
   (mark the fitment detail pages `noindex`, or show fitment only after the customer enters a
   VIN). Do not try to hide content from crawlers while still publishing it to them.

6. **Customer notice.** On the product page: "Fitment data is reference information. Our
   technician checks every order before it ships."

## Decision (2026-10-29): MEGA in production

The storage rules apply to any environment, including your laptop and staging. Until MEGA is
active:

- Explore the API with curl and the Playground. Note match rates and observations in our own
  words, but do not save responses.
- Build the import and search code against a `FakeCatalogClient` that returns hand-written
  fixtures with invented IDs. The real client and the fake share one interface.
- Do not commit real API responses as test fixtures.
- Switch on MEGA the day the first real import should write to the `catalog` schema. Expect
  that around week 2 of the build order. Run the 50-VIN test then.

## Fallback if we start below MEGA (no retention allowed)

The match can happen at request time, because the vendor's brand and part number are our own
data:

```
VIN ─► vin-check (live, not cached)
    ─► category list for vehicle (live)            shown as a fixed launch grid instead,
                                                    to save calls
    ─► customer picks category
    ─► articles for vehicle + category (live)
    ─► for each returned brand + number, look up our vendor_items by brand alias +
       normalised number (our DB) ─► show only what the vendor stocks
```

About 3 to 4 live calls per customer session, nothing stored from the API. The search is
slower (estimate 1 to 2 seconds) and quota scales with traffic instead of with stock. It is
an acceptable way to launch and learn, and the switch to the index design is a background job
once we upgrade.

## Questions to send the provider (draft)

> To: support@apiprofile.com
> Subject: Plan and data-use questions before subscribing, parts shop in Albania
>
> Hello,
>
> We are building an online parts shop in Albania. Our stock comes from local distributors.
> We plan to match each distributor SKU (brand + article number) to your catalog, and store
> the list of compatible vehicles for those SKUs only, not the full catalog. Before choosing
> a plan, could you confirm:
>
> 1. That this stocked-SKUs-only fitment index is permitted on MEGA, and the MEGA price,
>    monthly quota and request rate.
> 2. Whether we may cache VIN decode results for returning customers on MEGA.
> 3. Whether product pages that are indexed by search engines may show the compatible-vehicle
>    list and OE numbers for our own products.
> 4. Which media (article images, model photos, brand logos) we may display, and whether
>    hot-linking your CDN URLs is allowed.
> 5. How you notify customers of withdrawn or restricted records (email, API flag, other).
> 6. Whether get-compatible-cars-by-article-number returns all vehicles or a paged result.
> 7. Which countryFilterId you recommend for Albania, where most cars are used imports from
>    Germany and Italy.
>
> Thank you.

Store the reply next to this file. It counts as the written confirmation the terms refer to.
