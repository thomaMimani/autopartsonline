<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopCategory extends Model
{
    protected $fillable = ['slug', 'name_sq', 'name_en', 'synonyms', 'match_keywords', 'icon', 'sort'];

    protected function casts(): array
    {
        return ['synonyms' => 'array', 'match_keywords' => 'array'];
    }
}
