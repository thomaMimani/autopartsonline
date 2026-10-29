<?php

namespace App\Catalog;

use App\Catalog\Data\ArticleDetails;
use App\Catalog\Data\ArticleRef;
use App\Catalog\Data\VehicleRef;
use App\Catalog\Data\VinDecode;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * AutoPartsAPI v2 client. Server-side only; the key never reaches the browser.
 *
 * VERIFY BEFORE PRODUCTION: the public docs give no full response schemas, so the
 * field names in the pick() calls below are best guesses with fallbacks. Once the
 * MEGA plan is active, call each endpoint once, compare, and fix the key lists.
 * Do not commit the real responses (API terms §5).
 */
class ApiProfileCatalogClient implements CatalogClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $langId,
        private readonly int $countryFilterId,
        private readonly int $typeId,
        private readonly int $timeout = 20,
    ) {
        if ($baseUrl === '' || $apiKey === '') {
            throw new CatalogException('APIPROFILE_BASE_URL and APIPROFILE_API_KEY must be set for the api catalog driver.');
        }
    }

    public function listBrands(): array
    {
        $out = [];
        foreach ($this->rows($this->get('suppliers.list', '/api/suppliers/list')) as $row) {
            $id = $this->pick($row, ['supplierId', 'supId', 'dataSupplierId', 'id']);
            $name = $this->pick($row, ['supplierName', 'supBrand', 'brandName', 'name', 'brand']);
            if ($id !== null && $name !== null) {
                $out[(int) $id] = (string) $name;
            }
        }

        return $out;
    }

    public function findByBrandAndNumber(int $supplierId, string $articleNo): array
    {
        return $this->articles($this->get('articles.search-by-no-supplier', '/api/articles/search-by-articles-no-supplier-id', [
            'articleNo' => $articleNo, 'supplierId' => $supplierId, 'langId' => $this->langId,
        ]));
    }

    public function findByEan(string $ean): array
    {
        return $this->articles($this->get('artlookup.cross-numbers', sprintf(
            '/api/artlookup/search-for-cross-numbers/lang-id/%d/article-type/EAN/article-no/%s',
            $this->langId, rawurlencode($ean),
        )));
    }

    public function findByNumber(string $articleNo): array
    {
        return $this->articles($this->get('articles.search-by-no', '/api/articles/search-by-article-no', [
            'articleNo' => $articleNo, 'langId' => $this->langId,
        ]));
    }

    public function findByOeNumber(string $oeNumber): array
    {
        return $this->articles($this->get('articles-oem.search', '/api/articles-oem/search-by-article-oem-no', [
            'articleOemNo' => $oeNumber, 'langId' => $this->langId,
        ]));
    }

    public function findByPartialNumber(string $articleNo): array
    {
        return $this->articles($this->get('artlookup.partial', '/api/artlookup/select-article-cross-references-partial-match', [
            'articleNo' => $articleNo, 'langId' => $this->langId,
        ]));
    }

    public function articleDetails(int $articleId): ?ArticleDetails
    {
        $rows = $this->articles($this->get('articles.complete-details', "/api/articles/article-complete-details/type-id/{$this->typeId}", [
            'articleId' => $articleId, 'langId' => $this->langId,
        ]));
        if ($rows === []) {
            return null;
        }

        $oe = [];
        foreach ($this->rows($this->get('artlookup.oem-by-article', '/api/artlookup/search-for-cross-references-through-oem-numbers-by-article-id', [
            'articleId' => $articleId, 'langId' => $this->langId,
        ])) as $row) {
            $no = $this->pick($row, ['oemNumber', 'articleOemNo', 'oeNumber', 'oemNo', 'number']);
            if ($no !== null) {
                $oe[] = (string) $no;
            }
        }

        $criteria = [];
        foreach ($this->rows($this->get('articles.criteria', sprintf(
            '/api/articles/selection-of-all-specifications-criterias-for-the-article/article-id/%d/lang-id/%d/country-filter-id/%d',
            $articleId, $this->langId, $this->countryFilterId,
        ))) as $row) {
            $name = $this->pick($row, ['criteriaDescription', 'criteriaName', 'name', 'description']);
            $value = $this->pick($row, ['criteriaValue', 'formattedValue', 'value']);
            if ($name !== null) {
                $criteria[] = ['name' => (string) $name, 'value' => (string) ($value ?? '')];
            }
        }

        return new ArticleDetails($rows[0], array_values(array_unique($oe)), $criteria);
    }

    public function compatibleVehicles(string $articleNo, int $supplierId): array
    {
        $json = $this->get('articles.compatible-cars', "/api/articles/get-compatible-cars-by-article-number/type-id/{$this->typeId}", [
            'articleNo' => $articleNo, 'supplierId' => $supplierId,
            'langId' => $this->langId, 'countryFilterId' => $this->countryFilterId,
        ]);

        return array_values(array_filter(array_map($this->vehicle(...), $this->rows($json))));
    }

    public function decodeVin(string $vin): VinDecode
    {
        $json = $this->get('vin.check', '/api/vin/vin-check/'.rawurlencode($vin));
        $vehicles = array_values(array_filter(array_map($this->vehicle(...), $this->rows($json))));
        if ($vehicles !== []) {
            return new VinDecode($vehicles, 'vin-check', []);
        }

        // decoder-v5 returns make/model/year but no catalog vehicleId. The storefront then
        // falls back to the guided picker, pre-filled from this raw data.
        $raw = $this->get('vin.decoder-v5', '/api/vin/decoder-v5/'.rawurlencode($vin));

        return new VinDecode([], 'decoder-v5', is_array($raw) ? $raw : []);
    }

    // ---------------------------------------------------------------- internals

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->baseUrl, '/'))
            ->withHeaders(['x-apiprofile-key' => $this->apiKey])
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry(
                3,
                fn (int $attempt) => (2 ** $attempt) * 500 + random_int(0, 250),
                fn ($e) => $e instanceof ConnectionException
                    || ($e instanceof RequestException && in_array($e->response->status(), [429, 500, 502, 503, 504], true)),
                throw: false,
            );
    }

    private function get(string $endpoint, string $path, array $query = []): mixed
    {
        $response = $this->http()->get($path, $query);
        $this->count($endpoint, $response);

        return match (true) {
            $response->successful() => $response->json(),
            $response->status() === 404 => [], // docs: 404 also means "no results"
            $response->status() === 403 => throw new CatalogException('Catalog API quota exhausted or subscription inactive (403).'),
            $response->status() === 401 => throw new CatalogException('Catalog API key rejected (401).'),
            default => throw new CatalogException("Catalog API error {$response->status()} on $endpoint: ".($response->json('message') ?? 'no message')),
        };
    }

    private function count(string $endpoint, Response $response): void
    {
        DB::statement(
            'INSERT INTO catalog_api_usage (day, endpoint, calls, errors) VALUES (CURRENT_DATE, ?, 1, ?)
             ON CONFLICT (day, endpoint) DO UPDATE SET calls = catalog_api_usage.calls + 1, errors = catalog_api_usage.errors + EXCLUDED.errors',
            [$endpoint, $response->successful() || $response->status() === 404 ? 0 : 1],
        );
    }

    /** Find the list of records in a response, whatever key it sits under. */
    private function rows(mixed $json): array
    {
        if (! is_array($json) || $json === []) {
            return [];
        }
        if (array_is_list($json)) {
            return array_values(array_filter($json, 'is_array'));
        }
        foreach ($json as $value) {
            if (is_array($value) && $value !== [] && array_is_list($value) && is_array($value[0])) {
                return $value;
            }
        }

        return [$json];
    }

    private function pick(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return null;
    }

    /** @return list<ArticleRef> */
    private function articles(mixed $json): array
    {
        $out = [];
        foreach ($this->rows($json) as $row) {
            $id = $this->pick($row, ['articleId', 'legacyArticleId', 'id']);
            $sup = $this->pick($row, ['supplierId', 'dataSupplierId', 'supId']);
            $no = $this->pick($row, ['articleNo', 'articleNumber', 'number']);
            if ($id === null || $sup === null || $no === null) {
                continue;
            }
            $cat = $this->pick($row, ['categoryId', 'productId', 'genericArticleId', 'assemblyGroupNodeId']);
            $out[] = new ArticleRef(
                (int) $id,
                (int) $sup,
                (string) $no,
                $this->pick($row, ['supplierName', 'brandName', 'supBrand', 'mfrName']),
                $this->pick($row, ['articleProductName', 'productName', 'genericArticleDescription', 'name']),
                $cat === null ? null : (int) $cat,
                $this->pick($row, ['categoryName', 'productName', 'genericArticleDescription']),
                $this->pick($row, ['ean', 'eanNumber', 'gtin']),
            );
        }

        return $out;
    }

    private function vehicle(array $row): ?VehicleRef
    {
        $id = $this->pick($row, ['vehicleId', 'carId', 'linkingTargetId', 'typeId_vehicle', 'id']);
        if ($id === null) {
            return null;
        }
        $year = fn ($v) => $v === null ? null : (int) substr((string) $v, 0, 4);

        return new VehicleRef(
            (int) $id,
            $this->pick($row, ['manufacturerName', 'mfrName', 'manuName', 'make']),
            $this->pick($row, ['modelName', 'modelSeriesName', 'model']),
            $this->pick($row, ['typeEngineName', 'vehicleTypeDescription', 'carName', 'typeName', 'name']),
            ($kw = $this->pick($row, ['powerKw', 'kw', 'powerKwFrom'])) === null ? null : (int) $kw,
            ($hp = $this->pick($row, ['powerPs', 'hp', 'powerHpFrom'])) === null ? null : (int) $hp,
            ($cc = $this->pick($row, ['capacityCcm', 'ccmTech', 'cylinderCapacityCcm', 'ccm'])) === null ? null : (int) $cc,
            $this->pick($row, ['fuelType', 'fuel']),
            $year($this->pick($row, ['yearOfConstrFrom', 'constructionIntervalStart', 'yearFrom'])),
            $year($this->pick($row, ['yearOfConstrTo', 'constructionIntervalEnd', 'yearTo'])),
            (int) ($this->pick($row, ['typeId']) ?? $this->typeId),
        );
    }
}
