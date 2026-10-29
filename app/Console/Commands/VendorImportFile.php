<?php

namespace App\Console\Commands;

use App\Models\Vendor;
use App\Services\VendorImporter;
use Illuminate\Console\Command;

class VendorImportFile extends Command
{
    protected $signature = 'vendor:import {vendor : Vendor id or exact name} {path : CSV or XLSX file}';

    protected $description = 'Import a vendor price/stock file and queue new parts for matching';

    public function handle(VendorImporter $importer): int
    {
        $arg = $this->argument('vendor');
        $vendor = is_numeric($arg) ? Vendor::find($arg) : Vendor::where('name', $arg)->first();
        if (! $vendor) {
            $this->error("Vendor not found: $arg");

            return self::FAILURE;
        }

        $import = $importer->import($vendor, $this->argument('path'));
        $this->table(['rows', 'new', 'updated', 'invalid', 'status'], [[
            $import->rows_total, $import->rows_new, $import->rows_updated, $import->rows_invalid, $import->status,
        ]]);
        $this->line('Matching runs on the queue: php artisan queue:work');

        return self::SUCCESS;
    }
}
