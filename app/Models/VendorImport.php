<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorImport extends Model
{
    protected $fillable = ['vendor_id', 'source', 'file_path', 'status', 'rows_total', 'rows_new', 'rows_updated', 'rows_invalid', 'error'];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
