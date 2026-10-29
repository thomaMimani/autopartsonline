# 09 — Core flows (diagrams)

How the core feature works end to end: a vendor uploads a file, the system links each line to
the catalog, and a customer finds a part that fits by VIN. GitHub renders these Mermaid
diagrams as pictures.

Stack assumed: one Laravel app on Postgres. Storefront and the admin and vendor panels are
all Inertia React (storefront with SSR). No Filament. Background work runs on Laravel's database queue and scheduler.

## 1. The whole system on one page

```mermaid
flowchart LR
    subgraph People
        V["Vendor"]
        C["Customer"]
        A["Admin / technician"]
    end

    subgraph App["Laravel app"]
        VP["Vendor panel<br/>Inertia React"]
        SF["Storefront<br/>Inertia React + SSR"]
        AP["Admin panel<br/>Inertia React"]
        Q["Queue worker<br/>import, match, fitment jobs"]
        S["Scheduler<br/>daily stock pull, monthly refresh"]
    end

    DB[("Postgres<br/>vendor items, matches,<br/>articles, fitment, orders")]
    CAT["AutoPartsAPI<br/>catalog + VIN"]
    VAPI["Vendor's own API<br/>if they have one"]

    V -->|"uploads Excel / CSV"| VP
    VAPI -->|"pulled by"| S
    VP --> Q
    S --> Q
    Q <-->|"match + fitment calls<br/>once per new part"| CAT
    Q --> DB

    C -->|"VIN, category, order"| SF
    SF <-->|"VIN decode only<br/>cached 90 days"| CAT
    SF <--> DB

    A --> AP
    AP <--> DB
```

The key idea: the catalog API is called **when parts arrive**, not when customers search. The
only live call during a search is the VIN decode, and that is cached.

## 2. Vendor upload to sellable offer

```mermaid
flowchart TD
    U["Vendor uploads Excel / CSV<br/>or scheduler pulls vendor API"] --> P["Parse with this vendor's<br/>column profile"]
    P --> N["Normalise each line<br/>brand → brand_id via aliases<br/>part no → uppercase, strip spaces . - /"]
    N --> UP["Upsert vendor_items<br/>price, qty, lead time"]
    UP --> K{"Seen this brand + number<br/>before? part_matches"}
    K -->|"yes"| LINK["Link to existing article<br/>zero API calls"]
    K -->|"no"| JOB["Queue match job"]
    JOB --> LADDER["Matching ladder<br/>see diagram 3"]
    LADDER -->|"auto match"| SAVE["Save part_matches<br/>brand + number → article"]
    LADDER -->|"needs review"| REV["Admin review queue"]
    LADDER -->|"no match"| UNM["Unmatched list"]
    REV -->|"admin confirms"| SAVE
    UNM -->|"admin fixes alias<br/>or number"| JOB
    SAVE --> FIT["Queue fitment job<br/>see diagram 4"]
    FIT --> LINK
    LINK --> OFF{"qty > 0?"}
    OFF -->|"yes"| SELL["Visible as an offer<br/>in the storefront"]
    OFF -->|"no"| HIDE["Kept, not shown"]
```

`part_matches` is shared across vendors. When vendor 2 uploads a part vendor 1 already had,
it links instantly.

## 3. The matching ladder (one vendor line)

```mermaid
flowchart TD
    START["Line: brand_id + normalised number + EAN?"] --> S1["1. search-by-articles-no-supplier-id<br/>number + brand"]
    S1 -->|"exactly one hit"| AUTO["AUTO MATCH"]
    S1 -->|"none"| S2{"Has EAN?"}
    S2 -->|"yes"| E["2. search-for-cross-numbers<br/>article-type EAN"]
    E -->|"hit, same brand"| AUTO
    E -->|"hit, other brand"| REVIEW["REVIEW"]
    E -->|"none"| S3
    S2 -->|"no"| S3["3. search-by-article-no<br/>number only"]
    S3 -->|"one or more hits"| REVIEW
    S3 -->|"none"| OE{"Looks like an OE number?<br/>e.g. 1K0698151"}
    OE -->|"yes"| S5["5. OE path<br/>selecting-a-list-of-cars-for-oem-part-number<br/>fitment directly from OE number"]
    S5 -->|"cars found"| REVIEW
    OE -->|"no"| S4["4. partial-match search"]
    S4 -->|"candidates"| REVIEW
    S4 -->|"none"| NONE["UNMATCHED"]
```

Only step 1, and step 2 when the brand agrees, are trusted automatically. Everything else
waits for a human click in the admin panel.

## 4. Fitment job (once per newly matched article)

```mermaid
sequenceDiagram
    participant W as Queue worker
    participant API as AutoPartsAPI
    participant DB as Postgres

    W->>API: article-number-details (brand, number)
    API-->>W: name, images, category, articleId
    W->>DB: upsert articles

    W->>API: get-compatible-cars-by-article-number<br/>typeId=1, supplierId, countryFilterId
    API-->>W: list of vehicles
    W->>DB: upsert vehicles
    W->>DB: insert article_vehicles rows

    W->>API: OE cross-references by articleId
    API-->>W: OE numbers
    W->>DB: store oe_numbers on article<br/>(lets customers search by dealer number)
```

Cost is about 3 calls per new article, paid once. A monthly scheduler job refreshes
articles older than 30 days.

## 5. Customer: VIN to vehicle

```mermaid
flowchart TD
    IN["Customer types VIN"] --> VAL{"17 chars?<br/>auto-fix O→0, I→1, Q→0"}
    VAL -->|"invalid"| ERR["Show where to find the VIN<br/>on the registration document"]
    VAL -->|"valid"| CACHE{"In vin_lookups cache?"}
    CACHE -->|"yes"| CAND
    CACHE -->|"no"| VC["vin-check<br/>native, returns vehicleId"]
    VC -->|"found"| STORE["Save to cache"]
    VC -->|"not found"| V5["decoder-v5<br/>make, model, year, engine"]
    V5 -->|"decoded"| MAP["Map to catalog vehicles<br/>make + model + year + kW"]
    MAP --> STORE
    V5 -->|"nothing"| PICK["Guided picker<br/>make → model → year → fuel → kW"]
    STORE --> CAND{"How many candidates?"}
    CAND -->|"1"| CONF["Confirm screen<br/>name, years, kW/hp, fuel, photo"]
    CAND -->|"2-3"| CHOOSE["Choose card<br/>differences in plain words<br/>hint: kW is field P.2 on document"]
    CAND -->|"0"| PICK
    CHOOSE --> CONF
    PICK --> CONF
    CONF -->|"Yes, my car"| VEH["vehicle_id chosen<br/>saved in session / my cars"]
    CONF -->|"Not my car"| PICK
```

## 6. Customer: vehicle to order

```mermaid
sequenceDiagram
    actor C as Customer
    participant SF as Storefront
    participant DB as Postgres

    C->>SF: vehicle confirmed
    SF->>DB: categories with offers for this vehicle_id<br/>(offers ⋈ article_vehicles, one query)
    DB-->>SF: Ferrota (12), Disk freni (6), Filtër vaji (9) ...
    SF-->>C: category grid in Albanian, only what we can sell

    C->>SF: taps "Ferrota"
    SF->>DB: offers for vehicle + category, ranked<br/>in stock, brand tier, price
    DB-->>SF: offers
    SF-->>C: 1 recommended + cheaper + premium

    C->>SF: opens product
    SF->>DB: article + offer + fitment list + OE numbers
    SF-->>C: left: vendor price, stock<br/>right: catalog brand, number, OE, fits these cars

    C->>SF: add to cart, checkout<br/>name, phone, address, cash on delivery
    SF->>DB: create order + fit_check (pending)
    SF-->>C: order number + WhatsApp link with VIN and part
```

No catalog API calls happen anywhere in this diagram.

## 7. Order lifecycle with the technician check

```mermaid
stateDiagram-v2
    [*] --> Placed: customer checks out
    Placed --> FitCheck: auto, every order at launch
    FitCheck --> Confirmed: technician OK
    FitCheck --> NeedsCustomer: criteria unclear<br/>front/rear, brake system
    NeedsCustomer --> FitCheck: answered by phone / WhatsApp
    FitCheck --> Swapped: wrong part, technician picks right one
    Swapped --> Confirmed: customer agrees price
    FitCheck --> Cancelled: no fitting part in stock
    Confirmed --> SentToVendor: order line emailed / shown in vendor panel
    SentToVendor --> VendorShipped: vendor marks shipped
    SentToVendor --> Cancelled: vendor out of stock
    VendorShipped --> Delivered: cash collected
    Delivered --> Returned: wrong fit or other reason
    Delivered --> [*]
    Returned --> [*]
    Cancelled --> [*]
```

Every question the technician asks in `NeedsCustomer` is logged. That log becomes the
automatic question table in phase 2.

## 8. Keeping data fresh

```mermaid
flowchart LR
    subgraph Daily
        D1["Vendor uploads new file<br/>or scheduler pulls API"] --> D2["Update price + qty<br/>on existing vendor_items"]
        D2 --> D3["New lines only<br/>go to matching"]
        D4["Lines missing from file"] --> D5["qty = 0, hidden"]
    end
    subgraph Monthly
        M1["Articles fetched > 30 days ago"] --> M2["Re-fetch details + fitment"]
    end
    subgraph Always
        X1["VIN results cached 90 days"]
        X2["Brand list, manufacturers,<br/>models cached 7-30 days"]
    end
```

## 9. Core tables

```mermaid
erDiagram
    VENDORS ||--o{ VENDOR_ITEMS : uploads
    BRANDS ||--o{ VENDOR_ITEMS : "brand_id"
    BRANDS ||--o{ PART_MATCHES : ""
    PART_MATCHES }o--|| ARTICLES : "points to"
    VENDOR_ITEMS }o--o| PART_MATCHES : "brand + norm number"
    ARTICLES ||--o{ ARTICLE_VEHICLES : fits
    VEHICLES ||--o{ ARTICLE_VEHICLES : ""
    CATEGORIES ||--o{ ARTICLES : ""
    CATEGORIES ||--o{ CATEGORY_I18N : "Albanian names, slang"
    VIN_LOOKUPS }o--o{ VEHICLES : "candidate ids"
    ORDERS ||--|{ ORDER_ITEMS : ""
    ORDER_ITEMS }o--|| VENDOR_ITEMS : "offer bought"
    ORDERS ||--o| FIT_CHECKS : ""

    VENDOR_ITEMS {
        bigint id
        bigint vendor_id
        bigint brand_id
        string norm_article_no
        string raw_name
        decimal price
        int qty
        string status
    }
    PART_MATCHES {
        bigint brand_id
        string norm_article_no
        bigint article_id
        string method
        string confirmed_by
    }
    ARTICLES {
        bigint id
        bigint brand_id
        string article_no
        string name
        bigint category_id
        json oe_numbers
    }
    ARTICLE_VEHICLES {
        bigint article_id
        bigint vehicle_id
    }
    VEHICLES {
        bigint id
        string manufacturer
        string model
        int kw
        string fuel
        int year_from
        int year_to
    }
```

`offers` is not a table. It is a query: vendor items that are matched and have qty above 0.
