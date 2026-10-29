<?php

namespace App\Services;

use App\Catalog\CatalogClient;
use App\Models\Brand;
use App\Models\BrandAlias;
use App\Support\PartNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Turns the many ways vendors write a brand into one of our brand ids. */
class BrandResolver
{
    /** @var array<string, int>|null alias => brand_id */
    private ?array $aliases = null;

    public static function normalize(?string $value): string
    {
        return PartNumber::normalize(Str::ascii((string) $value));
    }

    public function resolve(?string $raw): ?int
    {
        $key = self::normalize($raw);

        return $key === '' ? null : ($this->aliases()[$key] ?? null);
    }

    /**
     * Find a brand inside free text such as "Ferrota BOSCH Golf 7".
     * Tries word sequences of 3, 2, then 1 words so "FEBI BILSTEIN" beats "FEBI".
     */
    public function fromText(?string $text): ?int
    {
        $words = preg_split('/[^A-Z0-9]+/', strtoupper(Str::ascii((string) $text)), -1, PREG_SPLIT_NO_EMPTY);
        $aliases = $this->aliases();
        for ($size = 3; $size >= 1; $size--) {
            for ($i = 0; $i + $size <= count($words); $i++) {
                $key = implode('', array_slice($words, $i, $size));
                if (strlen($key) >= 3 && isset($aliases[$key])) {
                    return $aliases[$key];
                }
            }
        }

        return null;
    }

    public function addAlias(int $brandId, string $alias): void
    {
        $key = self::normalize($alias);
        if ($key !== '') {
            BrandAlias::firstOrCreate(['alias' => $key], ['brand_id' => $brandId]);
            $this->aliases = null;
        }
    }

    /**
     * Load the catalog brand list, create our brands and aliases, and link them to
     * catalog supplier ids (stored in the catalog schema). Returns brands linked.
     */
    public function syncFromCatalog(CatalogClient $client): int
    {
        $tiers = config('brands.tiers', []);
        $linked = 0;
        foreach ($client->listBrands() as $supplierId => $name) {
            $name = trim($name);
            $brand = Brand::firstOrCreate(
                ['name' => $name],
                ['slug' => Str::slug(Str::ascii($name)) ?: 'brand-'.$supplierId, 'tier' => $tiers[$name] ?? 'standard'],
            );
            $this->addAlias($brand->id, $name);
            DB::statement(
                'INSERT INTO catalog.brand_links (brand_id, catalog_supplier_id, catalog_name) VALUES (?, ?, ?)
                 ON CONFLICT (brand_id) DO UPDATE SET catalog_supplier_id = EXCLUDED.catalog_supplier_id,
                   catalog_name = EXCLUDED.catalog_name, fetched_at = now()',
                [$brand->id, $supplierId, $name],
            );
            $linked++;
        }

        foreach (config('brands.aliases', []) as $name => $extra) {
            if ($brand = Brand::where('name', $name)->first()) {
                foreach ($extra as $alias) {
                    $this->addAlias($brand->id, $alias);
                }
            }
        }

        return $linked;
    }

    public function supplierIdFor(int $brandId): ?int
    {
        $id = DB::table('catalog.brand_links')->where('brand_id', $brandId)->value('catalog_supplier_id');

        return $id === null ? null : (int) $id;
    }

    public function brandIdForSupplier(int $supplierId): ?int
    {
        $id = DB::table('catalog.brand_links')->where('catalog_supplier_id', $supplierId)->value('brand_id');

        return $id === null ? null : (int) $id;
    }

    /** @return array<string, int> */
    private function aliases(): array
    {
        return $this->aliases ??= BrandAlias::pluck('brand_id', 'alias')->map(fn ($id) => (int) $id)->all();
    }
}
