<?php

namespace Tests\Unit;

use App\Services\BrandResolver;
use App\Services\VendorFileReader;
use App\Services\VinResolver;
use App\Support\PartNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PartNumberTest extends TestCase
{
    #[DataProvider('numbers')]
    public function test_normalize(string $raw, string $expected): void
    {
        $this->assertSame($expected, PartNumber::normalize($raw));
    }

    public static function numbers(): array
    {
        return [
            'bosch spaces' => ['0 986 494 123', '0986494123'],
            'ate dots and dash' => ['13.0460-7184.2', '13046071842'],
            'mann slash' => ['W 712/75', 'W71275'],
            'lowercase' => ['gdb1550', 'GDB1550'],
            'padding' => ['  P 85 075 ', 'P85075'],
        ];
    }

    public function test_looks_like_oe(): void
    {
        $this->assertTrue(PartNumber::looksLikeOe('1K0698151'));
        $this->assertTrue(PartNumber::looksLikeOe('5Q0615301AA'));
        $this->assertTrue(PartNumber::looksLikeOe('A6511800109'));
        $this->assertFalse(PartNumber::looksLikeOe('GDB1550'));
        $this->assertFalse(PartNumber::looksLikeOe('0986494123'));
    }

    public function test_brand_normalize_strips_accents(): void
    {
        $this->assertSame('LEMFORDER', BrandResolver::normalize('Lemförder'));
        $this->assertSame('MANNFILTER', BrandResolver::normalize('MANN-FILTER'));
    }

    #[DataProvider('prices')]
    public function test_parse_number(mixed $raw, ?float $expected): void
    {
        $this->assertSame($expected, VendorFileReader::parseNumber($raw));
    }

    public static function prices(): array
    {
        return [
            'albanian thousands' => ['4.200,00', 4200.0],
            'english thousands' => ['1,234.50', 1234.5],
            'comma decimal' => ['12,5', 12.5],
            'comma thousands' => ['1,250', 1250.0],
            'dot thousands' => ['2.500', 2500.0],
            'currency' => ['2 500 L', 2500.0],
            'plain' => ['1100', 1100.0],
            'greater than' => ['>10', 10.0],
            'empty' => ['', null],
            'native float' => [3.5, 3.5],
        ];
    }

    public function test_vin_clean_and_validate(): void
    {
        [$vin, $corrected] = VinResolver::clean(' wvw zzz-auzfwOOOOO1 ');
        $this->assertSame('WVWZZZAUZFW000001', $vin);
        $this->assertTrue($corrected);
        $this->assertTrue(VinResolver::isValid($vin));
        $this->assertFalse(VinResolver::isValid('WVWZZZAUZFW00000'));
    }
}
