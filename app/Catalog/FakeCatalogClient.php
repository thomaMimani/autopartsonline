<?php

namespace App\Catalog;

use App\Catalog\Data\ArticleDetails;
use App\Catalog\Data\ArticleRef;
use App\Catalog\Data\VehicleRef;
use App\Catalog\Data\VinDecode;
use App\Support\PartNumber;

/**
 * Hand-written fixtures for development and tests. Every ID, VIN and fitment here is
 * INVENTED. Nothing comes from the real API, so this is safe to commit and to store
 * before the MEGA plan is active.
 */
class FakeCatalogClient implements CatalogClient
{
    /** @var list<string> every call made, for asserting "no API call happened" */
    public array $calls = [];

    private const BRANDS = [
        9001 => 'BOSCH',
        9002 => 'MANN-FILTER',
        9003 => 'TRW',
        9004 => 'FEBI BILSTEIN',
        9005 => 'BREMBO',
    ];

    private const CATEGORIES = [
        50001 => 'Brake Pad Set, disc brake',
        50002 => 'Brake Disc',
        50003 => 'Oil Filter',
        50004 => 'Air Filter',
    ];

    /** articleId => [supplierId, articleNo, name, categoryId, ean, oe[], vehicleIds[]] */
    private const ARTICLES = [
        800001 => [9001, '0 986 494 123', 'Brake Pad Set, disc brake', 50001, '4047025000011', ['1K0698151', '5Q0698151'], [70001, 70002]],
        800002 => [9003, 'GDB1550', 'Brake Pad Set, disc brake', 50001, null, ['1K0698151'], [70001, 70002]],
        800003 => [9005, 'P 85 075', 'Brake Pad Set, disc brake', 50001, null, ['1K0698151'], [70001]],
        800004 => [9001, '0 986 479 321', 'Brake Disc', 50002, null, ['1K0615301AA'], [70001, 70002]],
        800005 => [9002, 'W 712/75', 'Oil Filter', 50003, null, ['03C115561H'], [70001, 70002, 70003]],
        800006 => [9004, '12345', 'Oil Filter', 50003, '4027816123456', ['A6511800109'], [70003]],
        800007 => [9001, '1234', 'Air Filter', 50004, null, [], [70003]],
        800008 => [9002, '1234', 'Air Filter', 50004, null, [], [70001]],
    ];

    private const VEHICLES = [
        70001 => ['VW', 'GOLF VII (5G1, BQ1, BE1, BE2)', '1.6 TDI', 77, 105, 1598, 'Diesel', 2013, 2017],
        70002 => ['VW', 'GOLF VII (5G1, BQ1, BE1, BE2)', '2.0 TDI', 110, 150, 1968, 'Diesel', 2013, 2020],
        70003 => ['MERCEDES-BENZ', 'C-CLASS (W204)', 'C 220 CDI', 125, 170, 2143, 'Diesel', 2008, 2014],
    ];

    /** VIN => candidate vehicleIds */
    private const VINS = [
        'WVWZZZAUZFW000001' => [70001],
        'WVWZZZAUZGW000002' => [70001, 70002],
        'WDD2040021A000003' => [70003],
    ];

    public function listBrands(): array
    {
        $this->calls[] = 'listBrands';

        return self::BRANDS;
    }

    public function findByBrandAndNumber(int $supplierId, string $articleNo): array
    {
        $this->calls[] = "findByBrandAndNumber:$supplierId:$articleNo";
        $norm = PartNumber::normalize($articleNo);

        return $this->where(fn ($a) => $a[0] === $supplierId && PartNumber::normalize($a[1]) === $norm);
    }

    public function findByEan(string $ean): array
    {
        $this->calls[] = "findByEan:$ean";

        return $this->where(fn ($a) => $a[4] === $ean);
    }

    public function findByNumber(string $articleNo): array
    {
        $this->calls[] = "findByNumber:$articleNo";
        $norm = PartNumber::normalize($articleNo);

        return $this->where(fn ($a) => PartNumber::normalize($a[1]) === $norm);
    }

    public function findByOeNumber(string $oeNumber): array
    {
        $this->calls[] = "findByOeNumber:$oeNumber";
        $norm = PartNumber::normalize($oeNumber);

        return $this->where(fn ($a) => in_array($norm, array_map(PartNumber::normalize(...), $a[5]), true));
    }

    public function findByPartialNumber(string $articleNo): array
    {
        $this->calls[] = "findByPartialNumber:$articleNo";
        $norm = PartNumber::normalize($articleNo);
        if (strlen($norm) < 4) {
            return [];
        }

        return $this->where(fn ($a) => str_contains(PartNumber::normalize($a[1]), $norm));
    }

    public function articleDetails(int $articleId): ?ArticleDetails
    {
        $this->calls[] = "articleDetails:$articleId";
        if (! isset(self::ARTICLES[$articleId])) {
            return null;
        }
        $a = self::ARTICLES[$articleId];

        return new ArticleDetails($this->ref($articleId, $a), $a[5], [['name' => 'Fitting Position', 'value' => 'Front Axle']]);
    }

    public function compatibleVehicles(string $articleNo, int $supplierId): array
    {
        $this->calls[] = "compatibleVehicles:$supplierId:$articleNo";
        $norm = PartNumber::normalize($articleNo);
        foreach (self::ARTICLES as $a) {
            if ($a[0] === $supplierId && PartNumber::normalize($a[1]) === $norm) {
                return array_map($this->vehicle(...), $a[6]);
            }
        }

        return [];
    }

    public function decodeVin(string $vin): VinDecode
    {
        $this->calls[] = "decodeVin:$vin";

        return new VinDecode(array_map($this->vehicle(...), self::VINS[$vin] ?? []), 'fake');
    }

    /** @return list<ArticleRef> */
    private function where(callable $filter): array
    {
        $out = [];
        foreach (self::ARTICLES as $id => $a) {
            if ($filter($a)) {
                $out[] = $this->ref($id, $a);
            }
        }

        return $out;
    }

    private function ref(int $id, array $a): ArticleRef
    {
        return new ArticleRef($id, $a[0], $a[1], self::BRANDS[$a[0]], $a[2], $a[3], self::CATEGORIES[$a[3]], $a[4]);
    }

    private function vehicle(int $id): VehicleRef
    {
        $v = self::VEHICLES[$id];

        return new VehicleRef($id, $v[0], $v[1], $v[2], $v[3], $v[4], $v[5], $v[6], $v[7], $v[8]);
    }
}
