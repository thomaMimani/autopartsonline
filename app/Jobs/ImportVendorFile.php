<?php

namespace App\Jobs;

use App\Models\Vendor;
use App\Services\VendorImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportVendorFile implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public function __construct(public int $vendorId, public string $path) {}

    public function handle(VendorImporter $importer): void
    {
        $importer->import(Vendor::findOrFail($this->vendorId), $this->path);
    }
}
