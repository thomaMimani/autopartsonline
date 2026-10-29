<?php

namespace App\Services;

use App\Jobs\MatchPart;
use App\Models\Vendor;
use App\Models\VendorImport;
use App\Models\VendorItem;
use App\Support\PartNumber;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Vendor file in, vendor_items updated, unknown parts queued for matching (diagram 2). */
class VendorImporter
{
    public function __construct(
        private readonly VendorFileReader $reader,
        private readonly BrandResolver $brands,
    ) {}

    public function import(Vendor $vendor, string $path, string $source = 'file'): VendorImport
    {
        $import = VendorImport::create(['vendor_id' => $vendor->id, 'source' => $source, 'file_path' => $path, 'status' => 'running']);
        $counts = ['rows_total' => 0, 'rows_new' => 0, 'rows_updated' => 0, 'rows_invalid' => 0];

        try {
            foreach ($this->reader->read($path, $vendor->file_profile ?? []) as $row) {
                $counts['rows_total']++;
                $norm = PartNumber::normalize($row['article_no']);
                if ($norm === '' || strlen($norm) < 3) {
                    $counts['rows_invalid']++;

                    continue;
                }

                $brandId = $this->brands->resolve($row['brand']) ?? $this->brands->fromText($row['name']);
                $item = VendorItem::firstOrNew([
                    'vendor_id' => $vendor->id,
                    'raw_brand' => (string) ($row['brand'] ?? ''),
                    'norm_article_no' => $norm,
                ]);
                $isNew = ! $item->exists;
                $brandChanged = $item->brand_id !== $brandId;

                $item->fill([
                    'brand_id' => $brandId,
                    'raw_article_no' => $row['article_no'],
                    'raw_name' => $row['name'],
                    'ean' => $row['ean'],
                    'price' => $row['price'],
                    'qty' => max(0, $row['qty']),
                    'lead_days' => $row['lead_days'],
                    'last_import_id' => $import->id,
                    'seen_at' => now(),
                ]);
                if ($isNew || $brandChanged) {
                    $item->match_status = VendorItem::PENDING;
                }
                $item->save();
                $counts[$isNew ? 'rows_new' : 'rows_updated']++;
            }

            // Lines missing from this file are out of stock, not deleted (diagram 8).
            VendorItem::where('vendor_id', $vendor->id)
                ->where(fn ($q) => $q->whereNull('last_import_id')->orWhere('last_import_id', '!=', $import->id))
                ->update(['qty' => 0]);

            $import->update($counts + ['status' => 'done']);
        } catch (Throwable $e) {
            $import->update($counts + ['status' => 'failed', 'error' => $e->getMessage()]);
            throw $e;
        }

        $this->queueMatching($vendor->id);

        return $import->refresh();
    }

    /** Pending lines: no brand -> unmatched (fix alias); otherwise one MatchPart per brand + number. */
    public function queueMatching(int $vendorId): int
    {
        VendorItem::where('vendor_id', $vendorId)->where('match_status', VendorItem::PENDING)
            ->whereNull('brand_id')->update(['match_status' => VendorItem::UNMATCHED]);

        $keys = DB::table('vendor_items')
            ->where('vendor_id', $vendorId)->where('match_status', VendorItem::PENDING)
            ->selectRaw('brand_id, norm_article_no, MIN(raw_article_no) AS raw_no, MAX(ean) AS ean')
            ->groupBy('brand_id', 'norm_article_no')
            ->get();

        foreach ($keys as $k) {
            MatchPart::dispatch((int) $k->brand_id, $k->norm_article_no, $k->raw_no, $k->ean);
        }

        return $keys->count();
    }
}
