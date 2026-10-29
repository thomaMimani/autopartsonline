<?php

namespace App\Console\Commands;

use App\Jobs\FetchArticleFitment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CatalogRefreshFitment extends Command
{
    protected $signature = 'catalog:refresh-fitment {--days= : Refresh articles older than this many days}';

    protected $description = 'Queue a fitment refresh for stocked articles fetched long ago';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('catalog.fitment_refresh_days'));
        $ids = DB::table('catalog.articles')
            ->where(fn ($q) => $q->whereNull('fitment_fetched_at')->orWhere('fitment_fetched_at', '<', now()->subDays($days)))
            ->pluck('id');
        $ids->each(fn ($id) => FetchArticleFitment::dispatch((int) $id));
        $this->info("Queued {$ids->count()} articles.");

        return self::SUCCESS;
    }
}
