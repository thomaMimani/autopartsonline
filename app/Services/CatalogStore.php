<?php

namespace App\Services;

use App\Catalog\Data\ArticleRef;
use App\Catalog\Data\VehicleRef;
use App\Models\ShopCategory;
use App\Support\PartNumber;
use Illuminate\Support\Facades\DB;

/** Writes catalog data into the `catalog` schema. The only place that does. */
class CatalogStore
{
    public function upsertArticle(ArticleRef $a, ?int $brandId): void
    {
        DB::statement(
            'INSERT INTO catalog.articles (id, catalog_supplier_id, brand_id, article_no, norm_article_no, name, catalog_category_id, ean, fetched_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, now())
             ON CONFLICT (id) DO UPDATE SET catalog_supplier_id = EXCLUDED.catalog_supplier_id,
               brand_id = COALESCE(EXCLUDED.brand_id, catalog.articles.brand_id), article_no = EXCLUDED.article_no,
               norm_article_no = EXCLUDED.norm_article_no, name = COALESCE(EXCLUDED.name, catalog.articles.name),
               catalog_category_id = COALESCE(EXCLUDED.catalog_category_id, catalog.articles.catalog_category_id),
               ean = COALESCE(EXCLUDED.ean, catalog.articles.ean), fetched_at = now()',
            [$a->articleId, $a->supplierId, $brandId, $a->articleNo, PartNumber::normalize($a->articleNo), $a->name, $a->categoryId, $a->ean],
        );

        if ($a->categoryId !== null) {
            $this->mapCategory($a->categoryId, $a->categoryName);
        }
    }

    public function upsertVehicle(VehicleRef $v): void
    {
        DB::statement(
            'INSERT INTO catalog.vehicles (id, type_id, manufacturer, model, name, kw, hp, ccm, fuel, year_from, year_to, fetched_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now())
             ON CONFLICT (id) DO UPDATE SET type_id = EXCLUDED.type_id, manufacturer = EXCLUDED.manufacturer,
               model = EXCLUDED.model, name = EXCLUDED.name, kw = EXCLUDED.kw, hp = EXCLUDED.hp, ccm = EXCLUDED.ccm,
               fuel = EXCLUDED.fuel, year_from = EXCLUDED.year_from, year_to = EXCLUDED.year_to, fetched_at = now()',
            [$v->vehicleId, $v->typeId, $v->manufacturer, $v->model, $v->name, $v->kw, $v->hp, $v->ccm, $v->fuel, $v->yearFrom, $v->yearTo],
        );
    }

    /**
     * Remember a catalog category and, if it is new, map it to an Albanian shop
     * category by keyword (e.g. "brake pad" -> ferrota). Unmapped ones wait for admin.
     */
    public function mapCategory(int $catalogCategoryId, ?string $catalogName): void
    {
        $existing = DB::table('catalog.category_map')->where('catalog_category_id', $catalogCategoryId)->first();
        if ($existing && $existing->shop_category_id !== null) {
            return;
        }

        $shopId = null;
        $haystack = mb_strtolower((string) $catalogName);
        if ($haystack !== '') {
            foreach (ShopCategory::orderBy('sort')->get() as $cat) {
                foreach ($cat->match_keywords ?? [] as $kw) {
                    if ($kw !== '' && str_contains($haystack, mb_strtolower($kw))) {
                        $shopId = $cat->id;
                        break 2;
                    }
                }
            }
        }

        DB::statement(
            'INSERT INTO catalog.category_map (catalog_category_id, catalog_name, shop_category_id) VALUES (?, ?, ?)
             ON CONFLICT (catalog_category_id) DO UPDATE SET catalog_name = COALESCE(EXCLUDED.catalog_name, catalog.category_map.catalog_name),
               shop_category_id = COALESCE(catalog.category_map.shop_category_id, EXCLUDED.shop_category_id)',
            [$catalogCategoryId, $catalogName, $shopId],
        );
    }
}
