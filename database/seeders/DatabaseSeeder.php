<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ShopCategorySeeder::class);

        if (app()->environment('local') && config('catalog.driver') === 'fake') {
            $this->call(DevSeeder::class);
        }
    }
}
