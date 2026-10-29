<?php

namespace Tests\Feature;

use App\Catalog\CatalogClient;
use App\Catalog\FakeCatalogClient;
use App\Models\Vendor;
use App\Models\VendorItem;
use App\Services\BrandResolver;
use App\Services\MatchingService;
use App\Services\PartSearch;
use App\Services\VendorImporter;
use App\Services\VinResolver;
use App\Services\VinResult;
use Database\Seeders\ShopCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** The core feature end to end, against the fake catalog (diagrams 2 to 6). */
class FitmentFlowTest extends TestCase
{
    use RefreshDatabase;

    private FakeCatalogClient $catalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShopCategorySeeder::class);
        $this->catalog = app(CatalogClient::class);
        app(BrandResolver::class)->syncFromCatalog($this->catalog);
    }

    private function importCsv(Vendor $vendor, string $csv): void
    {
        $path = tempnam(sys_get_temp_dir(), 'vendor').'.csv';
        file_put_contents($path, $csv);
        app(VendorImporter::class)->import($vendor, $path);
        unlink($path);
    }

    private function matchStatus(string $rawNo): string
    {
        return VendorItem::where('raw_article_no', $rawNo)->value('match_status');
    }

    public function test_import_matches_lines_by_the_right_rule(): void
    {
        $vendor = Vendor::create(['name' => 'A']);
        $this->importCsv($vendor, implode("\n", [
            'Marka;Kodi;Emertimi;EAN;Cmimi;Sasia',
            'BOSCH;0 986 494 123;Ferrota;;4.200,00;6',     // brand + number
            'MANN;W 712/75;Filter vaji;;1.250,00;10',      // alias MANN -> MANN-FILTER
            ';12345;Filter FEBI;4027816123456;1.100;3',    // brand from name
            'BOSCH;1234;Filter ajri;;900;2',               // two brands share "1234": brand decides
            'SUPERPARTS;SP-777;Ferrota;;1.900;5',          // unknown brand
            ';;bosh;;;',                                   // invalid line
        ]));

        $this->assertSame('matched', $this->matchStatus('0 986 494 123'));
        $this->assertSame('matched', $this->matchStatus('W 712/75'));
        $this->assertSame('matched', $this->matchStatus('12345'));
        $this->assertSame('matched', $this->matchStatus('1234'));
        $this->assertSame('unmatched', $this->matchStatus('SP-777'));
        $this->assertSame(800007, DB::table('catalog.part_matches')->where('norm_article_no', '1234')->value('article_id'));
        $this->assertSame(1, $vendor->imports()->first()->rows_invalid);
    }

    public function test_vin_search_shows_only_stocked_parts_that_fit(): void
    {
        $vendor = Vendor::create(['name' => 'A']);
        $this->importCsv($vendor, implode("\n", [
            'Marka;Kodi;Emertimi;Cmimi;Sasia',
            'BOSCH;0986494123;Ferrota Bosch;4200;6',
            'TRW;GDB1550;Ferrota TRW;3100;4',
            'BREMBO;P85075;Ferrota Brembo;5900;0',    // out of stock
            'FEBI;12345;Filter Mercedes;1100;3',      // fits only the Mercedes
        ]));

        $vin = app(VinResolver::class)->resolve('WVWZZZAUZFW000001');
        $this->assertSame(VinResult::OK, $vin->status);
        $golf = $vin->vehicles->first()->id;

        $search = app(PartSearch::class);
        $this->assertSame(['ferrota'], $search->categoriesForVehicle($golf)->pluck('slug')->all());

        $offers = $search->offers($golf, 'ferrota');
        $this->assertSame(['GDB1550', '0 986 494 123'], $offers->pluck('article_no')->all()); // premium tier, then price

        $product = $search->product(800001);
        $this->assertSame(['1K0698151', '5Q0698151'], $product['oe_numbers']);
        $this->assertCount(2, $product['fits']);
        $this->assertCount(1, $product['offers']);
    }

    public function test_search_makes_no_catalog_calls_and_vin_is_cached(): void
    {
        $vendor = Vendor::create(['name' => 'A']);
        $this->importCsv($vendor, "Marka;Kodi;Cmimi;Sasia\nBOSCH;0986494123;4200;6");

        $resolver = app(VinResolver::class);
        $resolver->resolve('WVWZZZAUZFW000001');
        $before = count($this->catalog->calls);

        $second = $resolver->resolve('WVWZZZAUZFW000001');
        app(PartSearch::class)->offers($second->vehicles->first()->id, 'ferrota');

        $this->assertTrue($second->fromCache);
        $this->assertSame($before, count($this->catalog->calls));
    }

    public function test_second_vendor_reuses_matches_without_api_calls(): void
    {
        $this->importCsv(Vendor::create(['name' => 'A']), "Marka;Kodi;Cmimi;Sasia\nBOSCH;0986494123;4200;6");
        $before = count($this->catalog->calls);

        $this->importCsv(Vendor::create(['name' => 'B']), "Brand;Code;Price;Stock\nBosch;0 986 494 123;4000;2");

        $this->assertSame($before, count($this->catalog->calls));
        $this->assertSame(2, VendorItem::where('match_status', 'matched')->count());
        $this->assertSame('4000.00', app(PartSearch::class)->product(800001)['offers']->first()->price);
    }

    public function test_ambiguous_and_unknown_vins(): void
    {
        $resolver = app(VinResolver::class);
        $this->assertSame(VinResult::AMBIGUOUS, $resolver->resolve('WVWZZZAUZGW000002')->status);
        $this->assertSame(VinResult::NOT_FOUND, $resolver->resolve('ZFA19900000000001')->status);
        $this->assertSame(VinResult::INVALID, $resolver->resolve('123')->status);
    }

    public function test_number_only_match_goes_to_review_and_admin_confirms(): void
    {
        // Vendor writes TRW's number under the wrong brand: step 3 finds it, a human decides.
        $vendor = Vendor::create(['name' => 'A']);
        $this->importCsv($vendor, "Marka;Kodi;Cmimi;Sasia\nBOSCH;GDB1550;3100;4");
        $this->assertSame('review', $this->matchStatus('GDB1550'));

        $boschId = app(BrandResolver::class)->resolve('BOSCH');
        app(MatchingService::class)->confirm($boschId, 'GDB1550', 800002, null);

        $this->assertSame('matched', $this->matchStatus('GDB1550'));
        $this->assertSame('manual', DB::table('catalog.part_matches')->where('norm_article_no', 'GDB1550')->value('method'));
        $this->assertSame(2, DB::table('catalog.article_vehicles')->where('article_id', 800002)->count());
    }

    public function test_reimport_zeroes_missing_lines(): void
    {
        $vendor = Vendor::create(['name' => 'A']);
        $this->importCsv($vendor, "Marka;Kodi;Cmimi;Sasia\nBOSCH;0986494123;4200;6\nTRW;GDB1550;3100;4");
        $this->importCsv($vendor, "Marka;Kodi;Cmimi;Sasia\nBOSCH;0986494123;4300;5");

        $this->assertSame(0, VendorItem::where('raw_article_no', 'GDB1550')->value('qty'));
        $this->assertSame('4300.00', VendorItem::where('raw_article_no', '0986494123')->value('price'));
    }

    public function test_purge_removes_catalog_data_and_keeps_ours(): void
    {
        $vendor = Vendor::create(['name' => 'A']);
        $this->importCsv($vendor, "Marka;Kodi;Cmimi;Sasia\nBOSCH;0986494123;4200;6");

        Artisan::call('catalog:purge', ['--force' => true]);

        $this->assertSame(0, DB::table('catalog.articles')->count());
        $this->assertSame(0, DB::table('catalog.brand_links')->count());
        $this->assertSame(1, VendorItem::count());
        $this->assertSame('pending', VendorItem::value('match_status'));
    }
}
