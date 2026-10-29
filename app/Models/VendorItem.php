<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorItem extends Model
{
    public const PENDING = 'pending';

    public const MATCHED = 'matched';

    public const REVIEW = 'review';

    public const UNMATCHED = 'unmatched';

    protected $fillable = [
        'vendor_id', 'brand_id', 'raw_brand', 'raw_article_no', 'norm_article_no', 'raw_name',
        'ean', 'price', 'qty', 'lead_days', 'match_status', 'last_import_id', 'seen_at',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'seen_at' => 'datetime'];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
