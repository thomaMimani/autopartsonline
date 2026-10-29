<?php

namespace App\Services;

use App\Catalog\CatalogClient;
use Illuminate\Support\Facades\DB;

/** Fetches details and the "fits these cars" list for one matched article (diagram 4). */
class FitmentService
{
    public function __construct(
        private readonly CatalogClient $client,
        private readonly CatalogStore $store,
    ) {}

    /** Returns the number of vehicles the article fits. */
    public function fetch(int $articleId): int
    {
        $article = DB::table('catalog.articles')->where('id', $articleId)->first();
        if (! $article) {
            return 0;
        }

        if ($details = $this->client->articleDetails($articleId)) {
            $this->store->upsertArticle($details->article, $article->brand_id);
            DB::table('catalog.articles')->where('id', $articleId)->update([
                'oe_numbers' => json_encode($details->oeNumbers),
                'criteria' => json_encode($details->criteria),
            ]);
        }

        $vehicles = $this->client->compatibleVehicles($article->article_no, (int) $article->catalog_supplier_id);

        DB::transaction(function () use ($articleId, $vehicles) {
            foreach ($vehicles as $v) {
                $this->store->upsertVehicle($v);
            }
            DB::table('catalog.article_vehicles')->where('article_id', $articleId)->delete();
            foreach (array_chunk(array_unique(array_map(fn ($v) => $v->vehicleId, $vehicles)), 500) as $chunk) {
                DB::table('catalog.article_vehicles')->insert(array_map(fn ($id) => ['article_id' => $articleId, 'vehicle_id' => $id], $chunk));
            }
            DB::table('catalog.articles')->where('id', $articleId)->update(['fitment_fetched_at' => now()]);
        });

        return count($vehicles);
    }
}
