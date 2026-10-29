# AutoPartsAPI v2 — Practical Developer Guide

> Source documentation: https://auto-parts-catalog.apiprofile.com/documentation  
> Prepared: 2026-10-29  
> Scope: A practical, beginner-friendly guide to the documentation visible on the source page. Endpoint paths and behaviors below are transcribed or summarized from that documentation. Where the source does not specify a detail (for example, exact response schemas or pagination), this guide says so rather than guessing.

## Table of contents

1. [What this API does](#1-what-this-api-does)
2. [Important concepts and IDs](#2-important-concepts-and-ids)
3. [Authentication and base URL](#3-authentication-and-base-url)
4. [Common parameters](#4-common-parameters)
5. [Quick start](#5-quick-start)
6. [Typical workflows](#6-typical-workflows)
7. [Endpoint reference](#7-endpoint-reference)
   - [Languages](#71-languages)
   - [Countries](#72-countries)
   - [Vehicle types and vehicle lookup](#73-vehicle-types-and-vehicle-lookup)
   - [Manufacturers](#74-manufacturers)
   - [Model series](#75-model-series)
   - [Categories / product groups](#76-categories--product-groups)
   - [Articles and parts — GET](#77-articles-and-parts--get)
   - [Articles and parts — POST](#78-articles-and-parts--post)
   - [OEM numbers](#79-oem-numbers)
   - [Cross-references and equivalent parts](#710-cross-references-and-equivalent-parts)
   - [Engines](#711-engines)
   - [Suppliers and media](#712-suppliers-and-media)
   - [VIN decoding](#713-vin-decoding)
8. [Article number types](#8-article-number-types)
9. [Errors, rate limits, and quotas](#9-errors-rate-limits-and-quotas)
10. [Caching and implementation advice](#10-caching-and-implementation-advice)
11. [Security checklist](#11-security-checklist)
12. [What the docs do not fully specify](#12-what-the-docs-do-not-fully-specify)
13. [Official source](#13-official-source)

---

## 1. What this API does

AutoPartsAPI is a REST API over an aftermarket vehicle-parts catalog. The documentation describes endpoints for:

- Vehicle makes/manufacturers, model series, and specific engine/vehicle variants.
- Product-group/category trees for a vehicle.
- Parts/articles compatible with a vehicle and category.
- Searching by aftermarket part number, OEM number, EAN, or other catalog numbers.
- Cross-references and equivalent parts across suppliers.
- Article details, fitment/specification criteria, sub-parts, accessories, images, PDFs, and diagrams.
- Engine specifications.
- VIN decoding using several approaches.

All API endpoints use the `/api/` prefix. The documentation calls this API Reference v2 and says v2 is the current stable version; there is no `/v2/` segment in the endpoint paths.

## 2. Important concepts and IDs

These IDs are not interchangeable:

| Name | Meaning |
|---|---|
| `typeId` | Vehicle category: passenger car, commercial vehicle, or motorbike. |
| `manufacturerId` | Manufacturer ID in the Aftermarket Catalog dataset. The manufacturers endpoint describes the returned field as `manuId`; verify the actual response field name. |
| `modelId` | Model series ID, such as a model family/series. |
| `vehicleId` | A specific catalog vehicle/engine variant inside a model series. Use this for compatibility lookups. |
| `categoryId` | Product-group/category ID, usually a leaf category for parts queries. |
| `articleId` | Internal catalog article/part record ID. |
| `productId` | Product identifier used by one article fitment-criteria endpoint. Do not assume it equals `articleId`. |
| `supplierId` | Aftermarket supplier ID. |
| `langId` | Language ID for localized labels and descriptions. |
| `countryFilterId` | Market/country filter used to scope catalog results. |
| `engId` | Engine ID, obtained from vehicle details when available. |
| `articleNo` | A part/catalog number supplied to a search endpoint. |
| `articleOemNo` | Original equipment (OEM) part number. |
| `vinNo` | Vehicle Identification Number. |

**Key lesson:** select IDs from API responses rather than hardcoding example IDs. IDs shown in the docs (such as BMW `manufacturerId=16`, `modelId=5626`, `vehicleId=19942`, or `categoryId=100260`) are examples and may depend on the dataset.

## 3. Authentication and base URL

Every request must include this HTTP header:

```http
x-apiprofile-key: YOUR_API_KEY
```

The documentation says to obtain the key from **Console → Developer Settings**. The service stores a SHA-256 hash rather than the raw API key.

### Key rotation

Use **Console → Developer Settings → Regenerate API Key**. The previous key is invalidated immediately, and the new raw key is displayed only once. Copy it securely before closing the dialog.

### Base URL

The documentation page displays a placeholder, `<your-base-url>`, rather than a concrete API hostname. Use the actual base URL supplied by your account/provider. Append endpoint paths exactly as documented, for example:

```text
<YOUR_BASE_URL>/api/languages/list
```

Do not send the literal angle-bracket placeholder in a real request.

### First request: verify the key

```bash
curl --request GET \
  --url "<YOUR_BASE_URL>/api/languages/list" \
  --header "x-apiprofile-key: YOUR_API_KEY"
```

A successful `200 OK` response indicates the request was accepted and the key worked for that endpoint.

### Keep the key private

- Do not commit it to Git or put it in frontend JavaScript, mobile app bundles, public repos, or screenshots.
- Prefer an environment variable or a server-side secret manager.
- If the key is exposed, rotate it immediately.
- Never log the full key.
- Treat VINs and any customer/vehicle information sent to your app as data that should be handled deliberately.

## 4. Common parameters

| Parameter | Type | Meaning / guidance |
|---|---|---|
| `typeId` | Integer | `1` = Passenger Car; `2` = Commercial Vehicle; `3` = Motorbike. Confirm supported values with `/api/types/list-vehicles-type`. |
| `langId` | Integer | Use `/api/languages/list` to get supported languages. Documentation examples: `4` English, `1` German, `19` French. |
| `countryFilterId` | Integer | Use `/api/countries/list` to discover values. Documentation says `63` is Germany and is the global default used in its examples; that does not mean it is always the correct market for your users. |
| `manufacturerId` | Integer | Resolve using `/api/manufacturers/list/type-id/{typeId}`. |
| `modelId` | Integer | Resolve using `/api/models/list/...`. |
| `vehicleId` | Integer | Resolve using `/api/types/type-id/{typeId}/list-vehicles-id/{modelId}/...`. It identifies a specific vehicle variant, not just a model family. |

The documentation recommends resolving common lookup IDs once and caching them because they change only when the dataset is updated. Follow any subscription/provider caching rules that apply to your account.

## 5. Quick start

This example follows the documentation's BMW 3-Series brake-pad workflow. The numbers are examples; use values returned by your own API responses.

### Step 1 — Get languages

```bash
curl -X GET "<YOUR_BASE_URL>/api/languages/list" \
  -H "x-apiprofile-key: YOUR_API_KEY"
```

Choose the `langId` that matches the language you want. Example: `4` for English.

### Step 2 — Get manufacturers for passenger cars

```bash
curl -X GET "<YOUR_BASE_URL>/api/manufacturers/list/type-id/1" \
  -H "x-apiprofile-key: YOUR_API_KEY"
```

Find BMW and copy its catalog manufacturer ID.

### Step 3 — List model series

```bash
curl -X GET "<YOUR_BASE_URL>/api/models/list/type-id/1/manufacturer-id/16/lang-id/4/country-filter-id/63" \
  -H "x-apiprofile-key: YOUR_API_KEY"
```

Choose the desired `modelId` from the response.

### Step 4 — List engine/vehicle variants

```bash
curl -X GET "<YOUR_BASE_URL>/api/types/type-id/1/list-vehicles-id/5626/lang-id/4/country-filter-id/63" \
  -H "x-apiprofile-key: YOUR_API_KEY"
```

Choose the exact `vehicleId`. If you need engine details to distinguish variants, use the `list-vehicles-types` endpoint below.

### Step 5 — Get part categories for that vehicle

For a nested category tree:

```bash
curl -X GET "<YOUR_BASE_URL>/api/category/type-id/1/products-groups-variant-2/19942/lang-id/4" \
  -H "x-apiprofile-key: YOUR_API_KEY"
```

For only the deepest category IDs that can be passed to the articles endpoint, use category variant 4.

### Step 6 — Fetch articles in a category

```bash
curl -X GET "<YOUR_BASE_URL>/api/articles/list/type-id/1/vehicle-id/19942/category-id/100260/lang-id/4" \
  -H "x-apiprofile-key: YOUR_API_KEY"
```

The documentation says this returns matching articles, part numbers, supplier information, and CDN image URLs.

### Standard vehicle-to-parts chain

```text
Languages + Countries
        ↓
Vehicle type (typeId)
        ↓
Manufacturer
        ↓
Model series (modelId)
        ↓
Specific vehicle / engine variant (vehicleId)
        ↓
Product category (categoryId)
        ↓
Articles / parts
        ↓
Article details, compatibility, media, and specifications
```

If you already have an article number or OEM number, you can skip the vehicle-selection chain and use article/OEM/cross-reference endpoints directly.

---

## 6. Typical workflows

### A. Browse parts for a vehicle

1. Resolve `typeId`, `langId`, and `countryFilterId`.
2. Find the manufacturer.
3. List model series and choose `modelId`.
4. List vehicle variants and choose the exact `vehicleId`.
5. Retrieve categories; for a fast parts lookup, use category variant 4.
6. Call the articles list endpoint with `vehicleId` and `categoryId`.
7. Use article detail/media/criteria endpoints as needed.

### B. Search by a part number

- Use `GET /api/articles/search-by-article-no?articleNo=...&langId=...` for a general search.
- Use `GET /api/articles/search-by-articles-no-supplier-id` if you know the supplier too.
- Use the POST quick-search endpoint if sending a JSON body is more convenient.
- If you need equivalent numbers, use cross-reference endpoints.

Encode query-string values correctly. In particular, encode spaces, `+`, `/`, `#`, and other reserved characters in part numbers/search terms.

### C. Search using an OEM number

1. Use `/api/articles-oem/search-by-article-oem-no` to find aftermarket parts associated with an OEM reference.
2. Use `/api/articles-oem/search-all-equal-oem-no/...` to find equal OEM numbers.
3. Use `/api/artlookup/` cross-reference endpoints to find aftermarket equivalents.
4. If compatibility matters, query the relevant vehicle/OEM compatibility endpoint and verify the exact vehicle variant.

### D. Show a parts diagram

1. Fetch article media information using `/api/articles/article-all-media-info`.
2. Obtain diagram/media URLs and the relevant filenames.
3. Request the diagram item coordinates using `/api/articles/selecting-item-coordinators-on-the-parts-diagram-image-for-the-parts-list/article-id/{articleId}`.
4. Use the coordinates to overlay clickable hotspots in your UI.

The docs mention X/Y coordinates but do not specify their coordinate system, image dimensions, or normalization rules. Confirm those details against actual responses before implementing hotspots.

### E. Decode a VIN

- Check whether the VIN is a 17-character string. The docs say standard VINs contain no `I`, `O`, or `Q`.
- Choose a decoder based on desired richness and latency.
- The native Aftermarket Catalog VIN endpoint requires a valid `VIN_API_KEY` configured on the server and consumes monthly quota.
- The structural parser is described as local and avoids an external API call.
- The aggregated decoder is recommended by the docs when you want results from multiple approaches.

VIN decoding is not guaranteed to identify every vehicle correctly. Validate decoded results before using them to select safety-critical parts.

---

## 7. Endpoint reference

All paths below are relative to `<YOUR_BASE_URL>`. Every request must include `x-apiprofile-key`. Unless the endpoint is explicitly described as a query-string or JSON-body endpoint, follow the path template exactly. The source documentation does not provide full example response bodies for every endpoint.

### 7.1 Languages

| Method | Path | Purpose | Parameters |
|---|---|---|---|
| GET | `/api/languages/list` | List all supported catalog languages. | None |
| GET | `/api/languages/get-language/lang-id/{langId}` | Get details for one language. | Path: `langId` (integer, required) |

Use the returned IDs instead of assuming language IDs are universal across APIs.

### 7.2 Countries

| Method | Path | Purpose | Parameters |
|---|---|---|---|
| GET | `/api/countries/list` | List countries using the default language. | None |
| GET | `/api/countries/list-countries-by-lang-id/{langId}` | List country names localized to a language. | Path: `langId` (integer, required) |
| GET | `/api/countries/get-country/lang-id/{langId}/country-filter-id/{countryFilterId}` | Get one country/market by ID. | Path: `langId`, `countryFilterId` (integers, required) |

The docs describe `countryFilterId=63` as Germany and a default used for broad coverage. Choose the correct market filter for your application rather than blindly relying on the example.

### 7.3 Vehicle types and vehicle lookup

| ID | Method and path | Purpose | Parameters |
|---|---|---|---|
| API-GTYP-001 | GET `/api/types/list-vehicles-type` | List supported `typeId` categories. | None |
| API-GTYP-002 | GET `/api/types/type-id/{typeId}/list-vehicles-id/{modelId}/lang-id/{langId}/country-filter-id/{countryFilterId}` | List vehicle variants with minimal ID/name information. | `typeId` required; `modelId`, `langId`, `countryFilterId` shown as optional in the docs. |
| API-GTYP-003 | GET `/api/types/type-id/{typeId}/list-vehicles-types/{modelId}/lang-id/{langId}/country-filter-id/{countryFilterId}` | List variants with engine specs such as kW, CC, cylinders, and engine codes. | `typeId` required; other listed values shown as optional. |
| API-GTYP-004 | GET `/api/types/type-id/{typeId}/vehicle-type-details/{vehicleId}/lang-id/{langId}/country-filter-id/{countryFilterId}` | Full vehicle specs: make/model/engine, power, displacement, valves, cylinders, fuel, production years, KBA numbers. | `typeId` required; other listed values shown as optional. |
| API-GTYP-005 | GET `/api/types/searching-the-passenger-car-by-ltn-number/lang-id/{langId}/country-filter-id/{countryFilterId}/ltn-number/{ltnNumber}/number-type/{ltnNumberType}` | Search passenger cars by certain registration-system or engine identifiers. | `langId` required; other values shown as optional. |
| API-GTYP-006 | GET `/api/types/selecting-all-criteria-for-spare-parts-of-a-passenger-car-using-an-olap-query/type-id/{typeId}/lang-id/{langId}/country-filter-id/{countryFilterId}/vehicle-id/{vehicleId}` | Return fitment criteria for parts compatible with a vehicle; useful for advanced filters. | All four path values required. |
| API-GTYP-007 | GET `/api/types/get-typeid-by-vehicleid?vehicleId={vehicleId}&manufacturerId={manufacturerId}` | Resolve a vehicle's `typeId` when given its vehicle ID (and optionally manufacturer ID). | Query: `vehicleId` required; `manufacturerId` optional. |

`ltnNumberType` values documented for API-GTYP-005:

| Value | Meaning |
|---:|---|
| `1` | German KBA number |
| `2` | Netherlands Kenteken |
| `3` | French Typemine |
| `4` | Engine code |
| `5` | Commercial-vehicle manufacturer number |

The endpoint name mentions passenger cars, while one accepted number type refers to commercial vehicles. Confirm the endpoint behavior for your intended vehicle class.

### 7.4 Manufacturers

| ID | Method and path | Purpose | Parameters |
|---|---|---|---|
| API-GMFA-001 | GET `/api/manufacturers/list/type-id/{typeId}` | List manufacturers for a vehicle category; response is described as including `countManufactures` and catalog IDs (`manuId`). | Path: `typeId` required. |
| API-GMFA-002 | GET `/api/manufacturers/find-by-id/{manufacturerId}` | Get one manufacturer's details and a CDN URL for its logo. | Path: `manufacturerId` required. |
| API-GMFA-003 | GET `/api/manufacturers/get-manufacturer-types/{manufacturerId}` | Find which vehicle types (PC/CV/motorbike) a manufacturer has in the dataset. | Path: `manufacturerId` required. |

Be careful about field naming: the common-parameters section calls the value `manufacturerId`, while the manufacturers response description mentions `manuId`. Inspect a real response and map the correct field in your code.

### 7.5 Model series

| ID | Method and path | Purpose | Parameters |
|---|---|---|---|
| API-GMDS-001 | GET `/api/models/list/type-id/{typeId}/manufacturer-id/{manufacturerId}/lang-id/{langId}/country-filter-id/{countryFilterId}` | List model series for a manufacturer; response includes `countModels` and `models`. | `typeId` required; remaining values shown as optional. |
| API-GMDS-002 | GET `/api/models/type-id/{typeId}/model-id/{modelId}/lang-id/{langId}/country-filter-id/{countryFilterId}` | Get a model series' name/details. | `typeId` required; other values shown as optional. |
| API-GMDS-003 | GET `/api/models/type-id/{typeId}/vehicles/{vehicleId}/lang-id/{langId}/country-filter-id/{countryFilterId}` | Resolve the model series for a known `vehicleId`; docs say internal routing handles PC/CV/motorbike. | `typeId` required; other values shown as optional. |
| API-GMDS-004 | GET `/api/models/type-id/{typeId}/model-id/{modelId}` | Get a model photo CDN URL (`modelImage`). | `typeId` required; `modelId` shown as optional. |

The model-photo endpoint is documented as available for passenger cars; it returns `null` for commercial vehicles and motorbikes.

### 7.6 Categories / product groups

The API offers multiple representations of category trees. Use the representation that fits your UI:

- Variant 1: flat list with depth.
- Variant 2: nested JSON with `children`, recommended for hierarchical navigation.
- Variant 3: ancestor-path representation, useful for breadcrumbs.
- Variant 4: deepest/leaf categories only, recommended when you need `categoryId` values for the articles endpoint.

| ID | Method and path | Purpose | Parameters |
|---|---|---|---|
| API-GCA-001 | GET `/api/category/type-id/{typeId}/products-groups-variant-1/{vehicleId}/lang-id/{langId}` | Vehicle-specific flat category list with depth. | All path values required. |
| API-GCA-002 | GET `/api/category/type-id/{typeId}/products-groups-variant-2/{vehicleId}/lang-id/{langId}` | Vehicle-specific nested category tree. | All path values required. |
| API-GCA-003 | GET `/api/category/type-id/{typeId}/products-groups-variant-3/{vehicleId}/lang-id/{langId}` | Vehicle-specific category tree based on full ancestor paths. | All path values required. |
| API-GCA-004 | GET `/api/category/type-id/{typeId}/products-groups-variant-4/{vehicleId}/lang-id/{langId}` | Only deepest category IDs; efficient for parts lookup. | All path values required. |
| API-GCA-005 | GET `/api/category/type-id/{typeId}/list-category-tree-structure/lang-id/{langId}` | Full category tree for a vehicle type, independent of a specific vehicle. | `typeId`, `langId` required. |
| API-GCA-006 | GET `/api/category/list-products-names/lang-id/{langId}` | Flat list of product-group names. | `langId` required. |
| API-GCA-007 | GET `/api/category/search-for-the-commodity-group-tree-by-description/type-id/{typeId}/lang-id/{langId}/search-text/{searchText}` | Search category descriptions and return a nested tree of matching groups. | `typeId`, `langId`, `searchText` required. |

Encode `searchText` as a path segment safely; if the search contains spaces or reserved characters, URL-encode it.

### 7.7 Articles and parts — GET

| ID | Method and path | Purpose | Parameters |
|---|---|---|---|
| API-GAR-001 | GET `/api/articles/list/type-id/{typeId}/vehicle-id/{vehicleId}/category-id/{categoryId}/lang-id/{langId}` | Main vehicle + category part list; includes article numbers, supplier information, and image URLs. | Values shown as optional in docs; use all relevant values. |
| API-GAR-002 | GET `/api/articles/details/article-id/{articleId}/lang-id/{langId}` | Article details by internal article ID. | `articleId`, `langId` required. |
| API-GAR-003 | GET `/api/articles/search-by-article-no?articleNo={articleNo}&langId={langId}` | Search using a part/catalog number. | Query: `articleNo` required; `langId` optional. |
| API-GAR-004 | GET `/api/articles/search-by-articles-no-supplier-id?articleNo={articleNo}&supplierId={supplierId}&langId={langId}` | Search by part number and supplier. | Query: `articleNo`, `supplierId` required; `langId` optional. |
| API-GAR-005 | GET `/api/articles/article-number-details/type-id/{typeId}?articleNo={articleNo}&supplierId={supplierId}&langId={langId}` | Complete details by article number. | Path: `typeId` required; query: `articleNo` required, `supplierId` and `langId` optional. |
| API-GAR-006 | GET `/api/articles/article-complete-details/type-id/{typeId}?articleId={articleId}&langId={langId}` | Complete details by article ID. | Path: `typeId` required; query: `articleId` required, `langId` optional. |
| API-GAR-007 | GET `/api/articles/article-all-media-info?articleId={articleId}&langId={langId}` | List associated media, including images, PDFs, and diagrams, with CDN URLs. | Query: `articleId` required; `langId` optional. |
| API-GAR-008 | GET `/api/articles/get-compatible-cars-by-article-number/type-id/{typeId}?articleNo={articleNo}&supplierId={supplierId}&langId={langId}&countryFilterId={countryFilterId}` | Find vehicles compatible with a part number. | Path: `typeId` required; query: `articleNo` required; other query values optional. |
| API-GAR-009 | GET `/api/articles/get-compatible-cars-by-article-number/type-id/{typeId}?articleNo={articleNo}&supplierId={supplierId}&langId={langId}&countryFilterId={countryFilterId}` | Variant requiring both part number and supplier for a precise fitment lookup. | Path: `typeId` required; query: `articleNo` and `supplierId` required; others optional. |
| API-GAR-0010 | GET `/api/articles/list-of-parts-for-article/article-id/{articleId}/lang-id/{langId}/country-filter-id/{countryFilterId}` | List sub-parts/components for an article. | All path values required. |
| API-GAR-0011 | GET `/api/articles/selecting-list-of-accessories-list-for-the-article/article-id/{articleId}/lang-id/{langId}/country-filter-id/{countryFilterId}` | List accessories for an article. | All path values required. |
| API-GAR-0012 | GET `/api/articles/selection-of-the-criteria-for-articles-and-vehicle/type-id/{typeId}/product-id/{productId}/vehicle-id/{vehicleId}/supplier-id/{supplierId}/lang-id/{langId}/country-filter-id/{countryFilterId}` | Fitment criteria for an article/product and vehicle. | All path values required. |
| API-GAR-0013 | GET `/api/articles/selection-of-all-specifications-criterias-for-the-article/article-id/{articleId}/lang-id/{langId}/country-filter-id/{countryFilterId}` | All specification criteria for an article. | All path values required. |
| API-GAR-0014 | GET `/api/articles/selecting-item-coordinators-on-the-parts-diagram-image-for-the-parts-list/article-id/{articleId}` | Get X/Y coordinates for items in a parts diagram. | `articleId` required. |
| API-GAR-0015 | GET `/api/articles/get-article-categories/article-id/{articleId}/lang-id/{langId}` | List all categories an article belongs to. | `articleId`, `langId` required. |
| API-GAR-0016 | GET `/api/articles/get-article-category/article-id/{articleId}/lang-id/{langId}` | Get the primary category for an article. | `articleId`, `langId` required. |

**Important source-document ambiguity:** API-GAR-008 and API-GAR-009 are both displayed with the same path template. The docs distinguish them by whether `supplierId` is optional or required. Confirm actual routing behavior with the provider or test environment before relying on a separate route.

### 7.8 Articles and parts — POST

The documentation says POST variants use JSON request bodies (`Content-Type: application/json`) for easier requests when long path parameters are inconvenient.

| ID | Method and path | JSON body fields | Purpose |
|---|---|---|---|
| API-PAR-001 | POST `/api/articles/list-articles` | `typeId`, `vehicleId`, `categoryId`, `langId` | Articles for a vehicle + category. |
| API-PAR-002 | POST `/api/articles/details` | `articleId`, `langId` | Article details. |
| API-PAR-003 | POST `/api/articles/quick-article-search` | `articleNo`, `langId` | Quick search by number. |
| API-PAR-004 | POST `/api/articles/article-number-details` | `typeId`, `articleNo`, `supplierId`, `langId` | Complete details by part number. |
| API-PAR-005 | POST `/api/articles/article-id-complete-details` | `typeId`, `articleId`, `langId` | Complete details by article ID. |
| API-PAR-006 | POST `/api/articles/article-all-media-info` | `articleId`, `langId` | All media for an article. |
| API-PAR-007 | POST `/api/articles/get-compatible-cars-by-article-number` | `typeId`, `articleNo`, `supplierId`, `langId`, `countryFilterId` | Compatible vehicles for a part number. |

Example request shape:

```bash
curl -X POST "<YOUR_BASE_URL>/api/articles/quick-article-search" \
  -H "x-apiprofile-key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"articleNo":"YOUR_PART_NUMBER","langId":4}'
```

Use the exact fields required by the endpoint. The docs do not specify whether unknown fields are ignored or rejected.

### 7.9 OEM numbers

| ID | Method and path | Purpose | Parameters |
|---|---|---|---|
| API-GARO-001 | GET `/api/articles-oem/search-by-article-oem-no?articleOemNo={articleOemNo}&langId={langId}` | Find aftermarket articles matching an OEM reference; response includes images. | `langId` required; `articleOemNo` is shown as optional in the parameter table, although it is central to the search. |
| API-GARO-002 | GET `/api/articles-oem/search-all-equal-oem-no/lang-id/{langId}/article-oem-no/{articleOemNo}` | Find equal OEM numbers. | Both path values required. |
| API-GARO-003 | GET `/api/articles-oem/selecting-a-list-of-cars-for-oem-part-number/type-id/{typeId}/lang-id/{langId}/country-filter-id/{countryFilterId}/manufacturer-id/{manufacturerId}/article-oem-no/{articleOemNo}` | Find vehicles compatible with an OEM part. | All path values required. |
| API-GARO-004 | GET `/api/articles-oem/selecting-oem-parts-vehicle-modification-description-product-group/type-id/{typeId}/vehicle-id/{vehicleId}/lang-id/{langId}/search-param/{searchParam}` | Search OEM parts for a vehicle using a search term. | All path values required. |

OEM means Original Equipment Manufacturer in this catalog context: the original equipment part reference used to look for matching aftermarket parts. A matching OEM number is useful evidence but should not replace checking exact vehicle fitment criteria.

### 7.10 Cross-references and equivalent parts

All these endpoints are under `/api/artlookup/`.

| ID | Method and path | Purpose | Parameters |
|---|---|---|---|
| API-GARL-001 | GET `/api/artlookup/select-article-cross-references/article-id/{articleId}/lang-id/{langId}` | All cross-references for an article. | `articleId`, `langId` required. |
| API-GARL-002 | GET `/api/artlookup/search-for-cross-references-through-oem-numbers/article-no/{articleNo}/supplierId/{supplierId}` | OEM cross-references for an aftermarket part. | `articleNo`, `supplierId` required. |
| API-GARL-003 | GET `/api/artlookup/search-for-the-oem-cross-references-through-aftermarket-parts-references/article-oem-no/{articleOemNo}` | Find aftermarket references for an OEM number. | `articleOemNo` required. |
| API-GARL-004 | GET `/api/artlookup/search-for-analogue-of-spare-parts-by-oem-number/article-oem-no/{articleOemNo}` | Find analogues by OEM number. | `articleOemNo` required. |
| API-GARL-005 | GET `/api/artlookup/search-for-analog-spare-parts-by-the-articles-numbers/lang-id/{langId}/articleNo/{articleNo}` | Find analogues by aftermarket article number. | `langId`, `articleNo` required. |
| API-GARL-006 | GET `/api/artlookup/search-for-cross-numbers/lang-id/{langId}/article-type/{articleType}/article-no/{articleNo}` | Find cross numbers by number type and number. | `langId`, `articleType`, `articleNo` required. |
| API-GARL-007 | GET `/api/artlookup/search-articles-by-article-no?articleNo={articleNo}&langId={langId}` | Article lookup by number. | Query: `articleNo` required; `langId` optional. |
| API-GARL-008 | GET `/api/artlookup/search-for-cross-references-through-oem-numbers-by-article-id?articleId={articleId}&langId={langId}` | OEM cross-references by article ID. | Query: `articleId` required; `langId` optional. |
| API-GARL-009 | GET `/api/artlookup/select-article-cross-references-partial-match?articleNo={articleNo}&langId={langId}` | Cross-references with a partial number match. | Query: `articleNo` required; `langId` optional. |

Cross-references are candidate equivalences, not an unconditional guarantee that parts are interchangeable. Verify the vehicle fitment, supplier, dimensions/specification criteria, and any catalog notes before treating a cross-reference as a confirmed match.

### 7.11 Engines

| ID | Method and path | Purpose | Parameters |
|---|---|---|---|
| API-GEN-001 | GET `/api/engines/engine-details/engine-id/{engId}/lang-id/{langId}` | Full engine specification: displacement, power, torque, valves, fuel type, manufacturer, and compatible vehicle list. | `engId` shown as optional; `langId` required. |

The docs say to obtain `engId` from vehicle-detail responses. Do not assume every vehicle response necessarily includes an engine ID; check the actual response.

### 7.12 Suppliers and media

| ID | Method and path | Purpose | Parameters |
|---|---|---|---|
| API-GSPL-001 | GET `/api/suppliers/list` | List suppliers with catalog `supplierId`, name, and brand details. | None. Cache because the list rarely changes. |
| API-GMD-001 | GET `/api/media/supplier-id/{supplierId}/media-type/{mediatype}/type/{type}/article-file-name/{articleFileName}` | Retrieve/stream/redirect to a media asset such as an image, PDF, or diagram. | All path values required. |

Documented `mediatype` examples: `IMAGE`, `PDF`, `OTHERS`.

The `articleFileName` comes from article detail responses. Use the exact supplier ID, media type, type, and filename from the API. The docs do not define all allowed values for `type`, nor do they fully explain whether the media endpoint returns a redirect, binary body, or a mixture in every case.

### 7.13 VIN decoding

VINs are described as 17-character alphanumeric identifiers with no `I`, `O`, or `Q`. The docs say VIN calls consume monthly quota; API-GVIN-001 specifically depends on a configured `VIN_API_KEY`.

| ID | Method and path | Purpose | Notes |
|---|---|---|---|
| API-GVIN-001 | GET `/api/vin/vin-check/{vinNo}` | Aftermarket Catalog-native VIN decode. | Requires a valid `VIN_API_KEY` configured on the server; returns a matched catalog vehicle including `typeId`, make, model, and engine specs. |
| API-GVIN-002 | GET `/api/vin/decoder-v1/{vinNo}` | Structural VIN parser. | Described as local parsing using WMI/VDS/VIS rules; returns manufacturer, country, year, plant, sequence number. |
| API-GVIN-003 | GET `/api/vin/decoder-v2/{vinNo}` | Curated VIN database lookup. | Can return richer attributes such as engine, trim, and manufacturing country. |
| API-GVIN-004 | GET `/api/vin/decoder-v3/{vinNo}` | External public VIN registry lookup. | Slower because it performs an outbound HTTP request. |
| API-GVIN-006 | GET `/api/vin/decoder-v5/{vinNo}` | Aggregated decoder. | Recommended by the docs when a more complete result is desired; combines results from v1, v2, and v3 under keys such as `vin-data-1`, `vin-data-2`, `vin-data-3`. |

There is no API-GVIN-005 listed on the documentation page. Do not infer that it exists. Because different VIN sources may disagree or return partial results, preserve the source/provenance of each result and avoid silently treating a partial decode as definitive.

---

## 8. Article number types

The cross-reference documentation lists these accepted `articleType` strings:

| Value | Meaning |
|---|---|
| `ArticleNumber` | Supplier's own aftermarket part number. |
| `IAMNumber` | Independent aftermarket number. |
| `EAN` | EAN-13 / barcode number. |
| `OENumber` | Original equipment (OEM) number. |
| `TradeNumber` | Trade/generic catalog number. |

Use the exact spelling and capitalization shown in the docs. Do not send a numeric enum unless a particular endpoint explicitly asks for one.

## 9. Errors, rate limits, and quotas

### HTTP status codes

The documentation says error responses include a JSON `message` field.

| Status | Meaning | Typical cause / response |
|---:|---|---|
| `200` | OK | Request succeeded. Validate the returned payload too. |
| `400` | Bad Request | Invalid parameter type or malformed request; check integer values and path/query/body placement. |
| `401` | Unauthorized | Missing or unrecognized `x-apiprofile-key`; verify the key and header. |
| `403` | Forbidden | Subscription inactive/suspended or monthly quota exhausted; check account/subscription status. |
| `404` | Not Found | Unknown route or no results for the query; distinguish an invalid path from a valid search with no match if the response allows it. |
| `429` | Too Many Requests | Requests-per-second limit exceeded; slow down and retry with backoff. |
| `500` | Internal Server Error | Unexpected server error; retry cautiously and contact support if it recurs. |

### Two simultaneous limits

1. **RPS (requests per second):** documented as a Redis fixed-window counter per subscription. Exceeding it produces `429`.
2. **Monthly quota:** usage-cycle counter resets on the subscription renewal date. Exhaustion produces `403`.

The docs say auto-renew on exhaustion can be enabled under **Console → Subscriptions**. It resets the quota when exhausted instead of blocking requests. Check the commercial implications of your subscription before enabling automatic renewal.

### Suggested retry strategy

- For `429`: exponential backoff with jitter; cap retries and respect any `Retry-After` header if the server sends one.
- For transient `500` errors: retry a small number of times with backoff.
- For `400`, `401`, and most `404` responses: fix the request rather than retrying unchanged.
- For `403`: check subscription status/quota rather than retrying repeatedly.
- Avoid retrying POST requests blindly unless you know the endpoint is safe to repeat.

These retry suggestions are general client-engineering advice; the documentation page does not publish exact backoff values or guarantee a `Retry-After` header.

## 10. Caching and implementation advice

### Good candidates to cache

The documentation explicitly recommends caching resolved common IDs and says the supplier list rarely changes. Reasonable candidates include:

- Languages and countries.
- Manufacturer lists per `typeId`.
- Model lists for a manufacturer/type/market/language combination.
- Vehicle variants for a model.
- Category trees for a vehicle.
- Supplier lists.

Use a cache key that includes all relevant scope parameters. For example, do not cache a model list only by `manufacturerId` if `typeId`, `langId`, or `countryFilterId` can affect results.

### Things to avoid

- Do not assume example IDs are permanent.
- Do not treat `modelId` as the same thing as `vehicleId`.
- Do not mix results from different language or country filters.
- Do not infer fitment from a name or a cross-reference alone.
- Do not fetch every endpoint on every page load if the data can be safely cached.
- Do not assume pagination exists: the docs page does not describe pagination parameters or response conventions. Inspect the actual API response and ask the provider if result sets can be truncated.

### Recommended application architecture

For a web application, keep API calls on your backend:

```text
Browser / frontend
      ↓ your application API
Backend service (stores API key securely)
      ↓ x-apiprofile-key
AutoPartsAPI
```

The backend can validate inputs, hide the secret, cache lookups, normalize errors, and prevent users from exhausting your subscription through uncontrolled requests.

### Validate parts before displaying “compatible”

For safety- or fitment-sensitive use cases, prefer a workflow that checks:

1. The exact `vehicleId`/engine variant.
2. Correct `countryFilterId` and `langId`.
3. Supplier and part number.
4. Fitment/specification criteria from the appropriate article endpoints.
5. Any relevant notes and sub-part/accessory relationships.

A catalog result is data from a provider, not a physical inspection or a guarantee that a part is legally suitable in every jurisdiction.

## 11. Security checklist

- [ ] API key is stored server-side in an environment variable or secret manager.
- [ ] API key is never included in frontend source, URLs, Git, or client logs.
- [ ] Requests use HTTPS where the provider supports it.
- [ ] User-provided path/query values are URL-encoded and validated.
- [ ] Logs redact API keys and avoid unnecessary VIN/customer data.
- [ ] Rate limits and quota usage are monitored.
- [ ] Retries are bounded; `429` and transient failures use backoff.
- [ ] VIN results and part-fitment results are validated before high-impact use.
- [ ] Key rotation procedure is documented for the team.

## 12. What the docs do not fully specify

The public documentation page is a reference, but it does not fully answer every implementation question. The following items should be confirmed with the provider or by testing a trial account:

- The concrete base URL for your account (the page shows a placeholder).
- Complete JSON schemas and sample responses for every endpoint.
- Pagination, sorting, filtering, and maximum response-size behavior.
- Exact meanings of all fields returned by each article/vehicle endpoint.
- Whether all endpoints require the same subscription tier and quota.
- Exact per-plan RPS and monthly quota numbers.
- Whether rate-limit responses include `Retry-After`.
- Full list of accepted values for the media endpoint's `type` parameter.
- Diagram coordinate units, origin, and scaling rules.
- The duplicated path template shown for API-GAR-008/API-GAR-009 and how the server distinguishes them.
- Why API-GVIN-005 is absent from the displayed VIN endpoint list.
- How `countryFilterId` changes catalog coverage for each country and vehicle type.
- Whether the service supports CORS, webhooks, or a formal OpenAPI/Swagger download; these are not established by the visible page.

When a parameter table marks a path value “optional,” but the path template contains that segment, test the endpoint carefully. An optional value inside a fixed path template can be ambiguous unless the server supports a specific omission convention.

## 13. Official source

- Documentation: https://auto-parts-catalog.apiprofile.com/documentation
- Use the live documentation as the authority for changes. This guide is a practical snapshot and may become outdated if the API changes.

---

## Appendix: copy-ready request templates

### GET request

```bash
curl --request GET \
  --url "<YOUR_BASE_URL>/api/languages/list" \
  --header "x-apiprofile-key: ${APIPROFILE_API_KEY}"
```

### GET with query parameters

```bash
curl --get \
  --url "<YOUR_BASE_URL>/api/articles/search-by-article-no" \
  --header "x-apiprofile-key: ${APIPROFILE_API_KEY}" \
  --data-urlencode "articleNo=YOUR_PART_NUMBER" \
  --data-urlencode "langId=4"
```

### POST with JSON

```bash
curl --request POST \
  --url "<YOUR_BASE_URL>/api/articles/quick-article-search" \
  --header "x-apiprofile-key: ${APIPROFILE_API_KEY}" \
  --header "Content-Type: application/json" \
  --data '{"articleNo":"YOUR_PART_NUMBER","langId":4}'
```

Replace all placeholders with values from your own account and API responses. Do not paste a real API key into public examples or documentation.
