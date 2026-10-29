<?php

namespace Database\Seeders;

use App\Models\ShopCategory;
use Illuminate\Database\Seeder;

/**
 * Albanian shop categories with garage slang (doc 08, section 7). Names and synonyms
 * are a starting point for the technician to correct. match_keywords map catalog
 * category names onto these automatically.
 */
class ShopCategorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['ferrota', 'Ferrota', 'Brake pads', ['ferodo', 'pllaka frenash', 'pastiglie', 'brake pads'], ['brake pad']],
            ['disk-freni', 'Disk freni', 'Brake discs', ['diskofren', 'disqe frenash', 'disk frenash', 'dischi freno'], ['brake disc']],
            ['filter-vaji', 'Filtër vaji', 'Oil filter', ['filtri i vajit', 'filtro olio'], ['oil filter']],
            ['filter-ajri', 'Filtër ajri', 'Air filter', ['filtri i ajrit', 'filtro aria'], ['air filter']],
            ['filter-kabine', 'Filtër kabine', 'Cabin filter', ['filtër poleni', 'filtri i kondicionerit', 'filtro abitacolo'], ['cabin', 'interior air', 'pollen']],
            ['filter-karburanti', 'Filtër karburanti', 'Fuel filter', ['filtër nafte', 'filtër benzine', 'filtro carburante'], ['fuel filter']],
            ['fshirese-xhamash', 'Fshirëse xhamash', 'Wiper blades', ['fshirëse', 'spazzole', 'tergicristalli'], ['wiper blade']],
            ['kandela', 'Kandela', 'Spark plugs', ['candele', 'spark plugs'], ['spark plug']],
            ['bateri', 'Bateri', 'Battery', ['akumulator', 'batteria'], ['starter battery', 'battery']],
            ['amortizator', 'Amortizator', 'Shock absorbers', ['amortizatorë', 'ammortizzatori'], ['shock absorber']],
            ['rrip-motori', 'Rrip motori', 'Timing belt', ['cinghia', 'rripi i shpërndarjes', 'rrip distribucioni'], ['timing belt', 'v-ribbed belt']],
            ['friksion', 'Friksion', 'Clutch', ['frizione', 'kompleti i friksionit'], ['clutch kit', 'clutch']],
        ];

        foreach ($rows as $i => [$slug, $sq, $en, $syn, $kw]) {
            ShopCategory::updateOrCreate(['slug' => $slug], [
                'name_sq' => $sq, 'name_en' => $en, 'synonyms' => $syn, 'match_keywords' => $kw, 'sort' => ($i + 1) * 10,
            ]);
        }
    }
}
