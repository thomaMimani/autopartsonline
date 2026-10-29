<?php

namespace App\Services;

use App\Catalog\CatalogClient;
use Illuminate\Support\Facades\DB;

/** VIN to catalog vehicle candidates (diagram 5). The only live catalog call at search time. */
class VinResolver
{
    public function __construct(
        private readonly CatalogClient $client,
        private readonly CatalogStore $store,
    ) {}

    /** Uppercase, drop spaces and dashes, and fix the letters VINs never contain: O->0, I->1, Q->0. */
    public static function clean(string $input): array
    {
        $vin = strtoupper(preg_replace('/[\s\-]/', '', $input) ?? '');
        $fixed = strtr($vin, ['O' => '0', 'I' => '1', 'Q' => '0']);

        return [$fixed, $fixed !== $vin];
    }

    public static function isValid(string $vin): bool
    {
        return (bool) preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vin);
    }

    public function resolve(string $input): VinResult
    {
        [$vin, $corrected] = self::clean($input);
        if (! self::isValid($vin)) {
            return new VinResult(VinResult::INVALID, $vin, $corrected);
        }

        $cached = DB::table('catalog.vin_lookups')->where('vin', $vin)
            ->where('created_at', '>=', now()->subDays(config('catalog.vin_cache_days')))->first();
        if ($cached) {
            $ids = $this->parseArray($cached->vehicle_ids);

            return $this->result($vin, $corrected, $ids, true, json_decode($cached->result ?? '[]', true) ?: []);
        }

        $decode = $this->client->decodeVin($vin);
        foreach ($decode->candidates as $v) {
            $this->store->upsertVehicle($v);
        }
        $ids = array_values(array_unique(array_map(fn ($v) => $v->vehicleId, $decode->candidates)));

        // Only successful decodes are cached; a miss is retried next time.
        if ($ids !== []) {
            DB::statement(
                'INSERT INTO catalog.vin_lookups (vin, vehicle_ids, decoder, result, created_at) VALUES (?, ?::bigint[], ?, ?::jsonb, now())
                 ON CONFLICT (vin) DO UPDATE SET vehicle_ids = EXCLUDED.vehicle_ids, decoder = EXCLUDED.decoder, result = EXCLUDED.result, created_at = now()',
                [$vin, '{'.implode(',', $ids).'}', $decode->decoder, json_encode($decode->raw)],
            );
        }

        return $this->result($vin, $corrected, $ids, false, $decode->raw);
    }

    private function result(string $vin, bool $corrected, array $ids, bool $fromCache, array $raw): VinResult
    {
        $vehicles = $ids === [] ? collect() : DB::table('catalog.vehicles')->whereIn('id', $ids)
            ->orderBy('manufacturer')->orderBy('model')->orderBy('kw')->get();
        $status = match (true) {
            $vehicles->count() === 1 => VinResult::OK,
            $vehicles->count() > 1 => VinResult::AMBIGUOUS,
            default => VinResult::NOT_FOUND,
        };

        return new VinResult($status, $vin, $corrected, $vehicles, $fromCache, $raw);
    }

    /** Postgres array literal "{1,2,3}" to ints. */
    private function parseArray(string $literal): array
    {
        return array_map('intval', array_filter(explode(',', trim($literal, '{}')), 'strlen'));
    }
}
