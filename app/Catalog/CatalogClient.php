<?php

namespace App\Catalog;

use App\Catalog\Data\ArticleDetails;
use App\Catalog\Data\ArticleRef;
use App\Catalog\Data\VehicleRef;
use App\Catalog\Data\VinDecode;

/**
 * The only door to the parts catalog. Two implementations:
 * FakeCatalogClient (fixtures) and ApiProfileCatalogClient (AutoPartsAPI v2).
 * Endpoint IDs refer to docs/reference/autopartsapi_v2_practical_guide.md.
 */
interface CatalogClient
{
    /** API-GSPL-001. @return array<int, string> catalog supplierId => brand name */
    public function listBrands(): array;

    /** API-GAR-004: brand + number. @return list<ArticleRef> */
    public function findByBrandAndNumber(int $supplierId, string $articleNo): array;

    /** API-GARL-006 with article-type EAN. @return list<ArticleRef> */
    public function findByEan(string $ean): array;

    /** API-GAR-003: number only, any brand. @return list<ArticleRef> */
    public function findByNumber(string $articleNo): array;

    /** API-GARO-001: aftermarket articles for an OE number. @return list<ArticleRef> */
    public function findByOeNumber(string $oeNumber): array;

    /** API-GARL-009: partial number match. @return list<ArticleRef> */
    public function findByPartialNumber(string $articleNo): array;

    /** API-GAR-006 + API-GARL-008 + API-GAR-0013. */
    public function articleDetails(int $articleId): ?ArticleDetails;

    /** API-GAR-009: vehicles an article fits. @return list<VehicleRef> */
    public function compatibleVehicles(string $articleNo, int $supplierId): array;

    /** API-GVIN-001, falling back to API-GVIN-006. */
    public function decodeVin(string $vin): VinDecode;
}
