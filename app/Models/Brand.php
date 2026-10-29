<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $fillable = ['name', 'slug', 'tier'];

    public function aliases(): HasMany
    {
        return $this->hasMany(BrandAlias::class);
    }
}
