<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Customer search after the vehicle is known (diagram 6). Reads only our database:
 * vendor stock joined to the fitment index. No catalog API calls.
 */
class PartSearch
{
    /** Sellable offers: matched part, in stock, priced, active vendor. */
    private const OFFERS = <<<'SQL'
        FROM catalog.article_vehicles av
        JOIN catalog.articles a       ON a.id = av.article_id
        JOIN catalog.part_matches pm  ON pm.article_id = a.id AND pm.status = 'matched'
        JOIN vendor_items vi          ON vi.brand_id = pm.brand_id AND vi.norm_article_no = pm.norm_article_no
                                     AND vi.qty > 0 AND vi.price IS NOT NULL
        JOIN vendors v                ON v.id = vi.vendor_id AND v.active
        JOIN catalog.category_map cm  ON cm.catalog_category_id = a.catalog_category_id
        JOIN shop_categories sc       ON sc.id = cm.shop_category_id
        JOIN brands b                 ON b.id = pm.brand_id
        WHERE av.vehicle_id = ?
        SQL;

    /** Category grid: only categories we can actually sell for this vehicle. */
    public function categoriesForVehicle(int $vehicleId): Collection
    {
        return collect(DB::select(
            'SELECT sc.id, sc.slug, sc.name_sq, sc.icon, COUNT(DISTINCT a.id) AS parts '.self::OFFERS.
            ' GROUP BY sc.id, sc.slug, sc.name_sq, sc.icon, sc.sort ORDER BY sc.sort, sc.name_sq',
            [$vehicleId],
        ));
    }

    /**
     * Offers for one vehicle and shop category, best first: premium/standard brands
     * before budget, then price. One row per article (its cheapest in-stock offer).
     */
    public function offers(int $vehicleId, string $categorySlug): Collection
    {
        return collect(DB::select(
            'SELECT * FROM (
                SELECT DISTINCT ON (a.id)
                    a.id AS article_id, a.article_no, a.name AS article_name, a.oe_numbers,
                    b.id AS brand_id, b.name AS brand, b.tier,
                    vi.id AS vendor_item_id, vi.raw_name, vi.price, vi.qty, vi.lead_days, v.name AS vendor
                '.self::OFFERS.' AND sc.slug = ?
                ORDER BY a.id, vi.price ASC
            ) o
            ORDER BY CASE o.tier WHEN \'premium\' THEN 1 WHEN \'standard\' THEN 2 ELSE 3 END, o.price ASC',
            [$vehicleId, $categorySlug],
        ));
    }

    /** Product page: vendor offers on one side, catalog reference and fitment on the other. */
    public function product(int $articleId): ?array
    {
        $article = DB::table('catalog.articles as a')->join('brands as b', 'b.id', '=', 'a.brand_id')
            ->where('a.id', $articleId)->select('a.*', 'b.name as brand')->first();
        if (! $article) {
            return null;
        }

        return [
            'article' => $article,
            'oe_numbers' => json_decode($article->oe_numbers, true) ?: [],
            'criteria' => json_decode($article->criteria, true) ?: [],
            'offers' => DB::table('catalog.part_matches as pm')
                ->join('vendor_items as vi', fn ($j) => $j->on('vi.brand_id', '=', 'pm.brand_id')->on('vi.norm_article_no', '=', 'pm.norm_article_no'))
                ->join('vendors as v', 'v.id', '=', 'vi.vendor_id')
                ->where('pm.article_id', $articleId)->where('pm.status', 'matched')->where('v.active', true)
                ->where('vi.qty', '>', 0)->whereNotNull('vi.price')
                ->orderBy('vi.price')->get(['vi.id', 'vi.raw_name', 'vi.price', 'vi.qty', 'vi.lead_days', 'v.name as vendor']),
            'fits' => DB::table('catalog.article_vehicles as av')->join('catalog.vehicles as ve', 've.id', '=', 'av.vehicle_id')
                ->where('av.article_id', $articleId)->orderBy('ve.manufacturer')->orderBy('ve.model')->orderBy('ve.year_from')
                ->get(['ve.*']),
        ];
    }
}
