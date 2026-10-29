<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = ['name', 'contact_name', 'phone', 'email', 'file_profile', 'active'];

    protected function casts(): array
    {
        return ['file_profile' => 'array', 'active' => 'boolean'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(VendorItem::class);
    }

    public function imports(): HasMany
    {
        return $this->hasMany(VendorImport::class);
    }
}
