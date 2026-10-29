<?php

namespace Database\Seeders;

use App\Catalog\CatalogClient;
use App\Models\User;
use App\Models\Vendor;
use App\Services\BrandResolver;
use App\Services\VendorImporter;
use Illuminate\Database\Seeder;

/** Local demo: admin, one vendor, brands from the (fake) catalog, and a sample file. */
class DevSeeder extends Seeder
{
    public function run(BrandResolver $brands, CatalogClient $client, VendorImporter $importer): void
    {
        User::updateOrCreate(['email' => 'admin@autoparts.test'], ['name' => 'Admin', 'password' => 'password', 'role' => 'admin']);

        $vendor = Vendor::firstOrCreate(['name' => 'Demo Vendor'], ['phone' => '+355 69 000 0000']);
        User::updateOrCreate(['email' => 'vendor@autoparts.test'], ['name' => 'Demo Vendor', 'password' => 'password', 'role' => 'vendor', 'vendor_id' => $vendor->id]);

        $brands->syncFromCatalog($client);
        $importer->import($vendor, database_path('seeders/data/demo-vendor.csv'));
    }
}
