<?php

namespace App\Console\Commands;

use App\Catalog\CatalogClient;
use App\Services\BrandResolver;
use Illuminate\Console\Command;

class CatalogSyncBrands extends Command
{
    protected $signature = 'catalog:sync-brands';

    protected $description = 'Load the catalog brand list, create brands and aliases, link them to catalog supplier ids';

    public function handle(BrandResolver $brands, CatalogClient $client): int
    {
        $this->info('Linked '.$brands->syncFromCatalog($client).' brands.');

        return self::SUCCESS;
    }
}
