<?php

namespace App\Console\Commands;

use App\Models\VendorItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Delete all data that came from the catalog API (terms §5: within 30 days of a downgrade). */
class CatalogPurge extends Command
{
    protected $signature = 'catalog:purge {--force}';

    protected $description = 'Delete every row in the catalog schema; our own data is kept';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Delete ALL catalog API data (fitment index, matches, VIN cache)?')) {
            return self::FAILURE;
        }

        $tables = collect(DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'catalog'"))->pluck('tablename');
        DB::statement('TRUNCATE '.$tables->map(fn ($t) => "catalog.\"$t\"")->implode(', ').' CASCADE');
        VendorItem::whereNotNull('brand_id')->update(['match_status' => VendorItem::PENDING]);
        $this->info('Purged '.$tables->count().' catalog tables. Vendor items reset to pending.');

        return self::SUCCESS;
    }
}
