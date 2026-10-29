<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Everything derived from the catalog API lives in the `catalog` schema.
 * Storing it is allowed only on the MEGA plan or higher, and it must be
 * deleted within 30 days of a downgrade (API terms §5). Purge with:
 *   php artisan catalog:purge
 * No foreign keys point from public tables into this schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE SCHEMA IF NOT EXISTS catalog;

-- Our brand  <->  catalog supplierId
CREATE TABLE catalog.brand_links (
    brand_id            bigint PRIMARY KEY,
    catalog_supplier_id bigint NOT NULL UNIQUE,
    catalog_name        text,
    fetched_at          timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE catalog.articles (
    id                  bigint PRIMARY KEY,          -- catalog articleId
    catalog_supplier_id bigint NOT NULL,
    brand_id            bigint,                      -- our brand, for joins
    article_no          text NOT NULL,
    norm_article_no     text NOT NULL,
    name                text,
    catalog_category_id bigint,
    ean                 text,
    oe_numbers          jsonb NOT NULL DEFAULT '[]',
    criteria            jsonb NOT NULL DEFAULT '[]',
    fitment_fetched_at  timestamptz,
    fetched_at          timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX articles_brand_no_idx ON catalog.articles (brand_id, norm_article_no);
CREATE INDEX articles_category_idx ON catalog.articles (catalog_category_id);

-- Shared across vendors: brand + normalised number -> catalog article.
CREATE TABLE catalog.part_matches (
    brand_id        bigint NOT NULL,
    norm_article_no text   NOT NULL,
    article_id      bigint REFERENCES catalog.articles(id) ON DELETE CASCADE,
    status          text   NOT NULL,   -- matched | review | unmatched | rejected
    method          text,              -- brand_number | ean | number_only | oe | partial | manual
    candidates      jsonb  NOT NULL DEFAULT '[]',
    confirmed_by    bigint,            -- users.id when a human confirmed
    updated_at      timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (brand_id, norm_article_no)
);
CREATE INDEX part_matches_article_idx ON catalog.part_matches (article_id);
CREATE INDEX part_matches_status_idx ON catalog.part_matches (status);

CREATE TABLE catalog.vehicles (
    id            bigint PRIMARY KEY,   -- catalog vehicleId
    type_id       smallint NOT NULL DEFAULT 1,
    manufacturer  text,
    model         text,
    name          text,
    kw            integer,
    hp            integer,
    ccm           integer,
    fuel          text,
    year_from     smallint,
    year_to       smallint,
    fetched_at    timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX vehicles_make_model_idx ON catalog.vehicles (manufacturer, model);

-- The fitment index: which article fits which vehicle.
CREATE TABLE catalog.article_vehicles (
    article_id bigint NOT NULL REFERENCES catalog.articles(id) ON DELETE CASCADE,
    vehicle_id bigint NOT NULL REFERENCES catalog.vehicles(id) ON DELETE CASCADE,
    PRIMARY KEY (vehicle_id, article_id)
);
CREATE INDEX article_vehicles_article_idx ON catalog.article_vehicles (article_id);

-- Catalog category -> our Albanian shop category.
CREATE TABLE catalog.category_map (
    catalog_category_id bigint PRIMARY KEY,
    catalog_name        text,
    shop_category_id    bigint            -- public.shop_categories.id, no FK on purpose
);

CREATE TABLE catalog.vin_lookups (
    vin         char(17) PRIMARY KEY,
    vehicle_ids bigint[] NOT NULL DEFAULT '{}',
    decoder     text,
    result      jsonb,
    created_at  timestamptz NOT NULL DEFAULT now()
);
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP SCHEMA IF EXISTS catalog CASCADE');
    }
};
