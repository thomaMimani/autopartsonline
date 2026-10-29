<?php

namespace App\Services;

use App\Catalog\CatalogClient;
use App\Catalog\Data\ArticleRef;
use App\Jobs\FetchArticleFitment;
use App\Models\VendorItem;
use App\Support\PartNumber;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The matching ladder (docs/09-core-flows.md, diagram 3).
 * Key: our brand id + normalised article number. Results are stored in
 * catalog.part_matches and shared by every vendor that stocks the same part.
 */
class MatchingService
{
    public function __construct(
        private readonly CatalogClient $client,
        private readonly BrandResolver $brands,
        private readonly CatalogStore $store,
    ) {}

    /**
     * Match one part. Returns the resulting status: matched | review | unmatched.
     * Already decided keys are not sent to the API again unless $force is true.
     */
    public function match(int $brandId, string $normNo, ?string $rawNo = null, ?string $ean = null, bool $force = false): string
    {
        $existing = $this->existing($brandId, $normNo);
        if ($existing && ! $force && in_array($existing->status, ['matched', 'review', 'rejected'], true)) {
            $this->syncVendorItems($brandId, $normNo, $existing->status);

            return $existing->status;
        }

        $query = $rawNo ?: $normNo;
        $supplierId = $this->brands->supplierIdFor($brandId);
        $same = fn (ArticleRef $a) => PartNumber::normalize($a->articleNo) === $normNo;

        // 1. Brand + number: the only fully trusted step.
        if ($supplierId !== null) {
            $hits = array_values(array_filter($this->client->findByBrandAndNumber($supplierId, $query), $same));
            if (count($hits) === 1) {
                return $this->accept($brandId, $normNo, $hits[0], 'brand_number');
            }
            if (count($hits) > 1) {
                return $this->review($brandId, $normNo, $hits, 'brand_number');
            }
        }

        // 2. EAN: trusted only when the brand agrees.
        if ($ean) {
            $hits = $this->client->findByEan($ean);
            $sameBrand = array_values(array_filter($hits, fn (ArticleRef $a) => $supplierId !== null && $a->supplierId === $supplierId));
            if (count($sameBrand) === 1) {
                return $this->accept($brandId, $normNo, $sameBrand[0], 'ean');
            }
            if ($hits !== []) {
                return $this->review($brandId, $normNo, $hits, 'ean');
            }
        }

        // 3. Number only, any brand: always a human decision.
        $hits = array_values(array_filter($this->client->findByNumber($query), $same));
        if ($hits !== []) {
            return $this->review($brandId, $normNo, $hits, 'number_only');
        }

        // 4. Looks like a dealer (OE) number: show aftermarket candidates.
        if (PartNumber::looksLikeOe($normNo)) {
            $hits = $this->client->findByOeNumber($query);
            if ($hits !== []) {
                return $this->review($brandId, $normNo, $hits, 'oe');
            }
        }

        // 5. Partial match: review only.
        $hits = $this->client->findByPartialNumber($query);
        if ($hits !== []) {
            return $this->review($brandId, $normNo, array_slice($hits, 0, 20), 'partial');
        }

        $this->save($brandId, $normNo, null, 'unmatched', null, []);

        return 'unmatched';
    }

    /** Admin confirms one of the review candidates. */
    public function confirm(int $brandId, string $normNo, int $articleId, ?int $userId): void
    {
        $row = $this->existing($brandId, $normNo);
        $candidate = collect(json_decode($row->candidates ?? '[]', true))->firstWhere('articleId', $articleId);
        if (! $candidate) {
            throw new InvalidArgumentException("Article $articleId is not a candidate for $brandId/$normNo.");
        }
        $this->accept($brandId, $normNo, new ArticleRef(...$candidate), 'manual', $userId);
    }

    /** Admin says none of the candidates is right. */
    public function reject(int $brandId, string $normNo, ?int $userId): void
    {
        DB::table('catalog.part_matches')->where(['brand_id' => $brandId, 'norm_article_no' => $normNo])
            ->update(['status' => 'rejected', 'article_id' => null, 'confirmed_by' => $userId, 'updated_at' => now()]);
        $this->syncVendorItems($brandId, $normNo, 'rejected');
    }

    private function accept(int $brandId, string $normNo, ArticleRef $article, string $method, ?int $userId = null): string
    {
        $this->store->upsertArticle($article, $this->brands->brandIdForSupplier($article->supplierId) ?? $brandId);
        $this->save($brandId, $normNo, $article->articleId, 'matched', $method, [], $userId);
        FetchArticleFitment::dispatch($article->articleId);

        return 'matched';
    }

    /** @param list<ArticleRef> $candidates */
    private function review(int $brandId, string $normNo, array $candidates, string $method): string
    {
        $this->save($brandId, $normNo, null, 'review', $method, array_map(fn (ArticleRef $a) => $a->toArray(), $candidates));

        return 'review';
    }

    private function save(int $brandId, string $normNo, ?int $articleId, string $status, ?string $method, array $candidates, ?int $userId = null): void
    {
        DB::statement(
            'INSERT INTO catalog.part_matches (brand_id, norm_article_no, article_id, status, method, candidates, confirmed_by, updated_at)
             VALUES (?, ?, ?, ?, ?, ?::jsonb, ?, now())
             ON CONFLICT (brand_id, norm_article_no) DO UPDATE SET article_id = EXCLUDED.article_id, status = EXCLUDED.status,
               method = EXCLUDED.method, candidates = EXCLUDED.candidates, confirmed_by = EXCLUDED.confirmed_by, updated_at = now()',
            [$brandId, $normNo, $articleId, $status, $method, json_encode($candidates), $userId],
        );
        $this->syncVendorItems($brandId, $normNo, $status);
    }

    private function syncVendorItems(int $brandId, string $normNo, string $status): void
    {
        VendorItem::where('brand_id', $brandId)->where('norm_article_no', $normNo)
            ->update(['match_status' => $status === 'rejected' ? VendorItem::UNMATCHED : $status]);
    }

    private function existing(int $brandId, string $normNo): ?object
    {
        return DB::table('catalog.part_matches')->where(['brand_id' => $brandId, 'norm_article_no' => $normNo])->first();
    }
}
