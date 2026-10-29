<?php

namespace App\Services;

use App\Support\PartNumber;
use Illuminate\Support\Str;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;

/**
 * Reads a vendor's CSV or XLSX into rows with our field names:
 * brand, article_no, name, ean, price, qty, lead_days.
 * Headers are detected automatically (Albanian, Italian and English names), and a
 * vendor's file_profile can override them: {"columns": {"article_no": "Kodi i artikullit"}}.
 */
class VendorFileReader
{
    /** Normalised header spellings per field. */
    private const HEADERS = [
        'brand' => ['BRAND', 'MARKA', 'PRODHUESI', 'MANUFACTURER', 'MARCA', 'PRODUCER', 'BRANDNAME'],
        'article_no' => ['ARTICLENO', 'ARTICLENUMBER', 'KODI', 'KOD', 'CODE', 'SKU', 'PARTNO', 'PARTNUMBER', 'ARTIKULLI', 'NRARTIKULLI', 'CODICE', 'REF', 'REFERENCE'],
        'name' => ['NAME', 'EMRI', 'EMERTIMI', 'PERSHKRIMI', 'DESCRIPTION', 'DESCRIZIONE', 'PRODUCT', 'ARTIKULL'],
        'ean' => ['EAN', 'BARCODE', 'BARKOD', 'GTIN'],
        'price' => ['PRICE', 'CMIMI', 'CMIM', 'PREZZO', 'PRICEALL', 'CMIMIALL'],
        'qty' => ['QTY', 'QUANTITY', 'SASIA', 'STOCK', 'GJENDJA', 'STOK', 'MAGAZINA', 'QUANTITA'],
        'lead_days' => ['LEADDAYS', 'LEADTIME', 'DITE', 'AFATI'],
    ];

    /** @return \Generator<int, array{brand: ?string, article_no: ?string, name: ?string, ean: ?string, price: ?float, qty: int, lead_days: ?int}> */
    public function read(string $path, array $profile = []): \Generator
    {
        $reader = $this->open($path);
        $reader->open($path);
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $map = null;
                foreach ($sheet->getRowIterator() as $row) {
                    $cells = array_map(fn ($v) => $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : trim((string) $v), $row->toArray());
                    if ($map === null) {
                        $map = $this->headerMap($cells, $profile['columns'] ?? []);
                        if (! isset($map['article_no'])) {
                            throw new RuntimeException('No part number column found. Headers: '.implode(', ', $cells));
                        }

                        continue;
                    }
                    if (implode('', $cells) === '') {
                        continue;
                    }
                    $get = fn (string $f) => isset($map[$f]) ? ($cells[$map[$f]] ?? null) : null;
                    yield [
                        'brand' => $get('brand') ?: null,
                        'article_no' => $get('article_no') ?: null,
                        'name' => $get('name') ?: null,
                        'ean' => ($e = preg_replace('/\D/', '', (string) $get('ean'))) !== '' ? $e : null,
                        'price' => self::parseNumber($get('price')),
                        'qty' => (int) floor(self::parseNumber($get('qty')) ?? 0),
                        'lead_days' => ($d = self::parseNumber($get('lead_days'))) === null ? null : (int) $d,
                    ];
                }
                break; // first sheet only
            }
        } finally {
            $reader->close();
        }
    }

    /** "1.234,50" -> 1234.5, "1,234.50" -> 1234.5, "12,5" -> 12.5, "2 500 L" -> 2500, ">10" -> 10 */
    public static function parseNumber(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        $s = preg_replace('/[^0-9,.\-]/', '', (string) $value);
        if ($s === '' || $s === '-') {
            return null;
        }
        $comma = strrpos($s, ',');
        $dot = strrpos($s, '.');
        if ($comma !== false && $dot !== false) {
            $decimal = $comma > $dot ? ',' : '.';
            $s = str_replace($decimal === ',' ? '.' : ',', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif ($comma !== false) {
            // "1,250" and "12,500,000" are thousands; "12,5" and "12,50" are decimals
            $s = preg_match('/^-?\d{1,3}(,\d{3})+$/', $s) ? str_replace(',', '', $s) : str_replace(',', '.', $s);
        } elseif ($dot !== false && preg_match('/^\d{1,3}(\.\d{3})+$/', $s)) {
            $s = str_replace('.', '', $s); // "1.250" thousands
        }

        return is_numeric($s) ? (float) $s : null;
    }

    private function open(string $path): CsvReader|XlsxReader
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'xlsx') {
            return new XlsxReader;
        }
        if (! in_array($ext, ['csv', 'txt'], true)) {
            throw new RuntimeException("Unsupported file type .$ext. Use .xlsx or .csv (save old .xls files as .xlsx).");
        }
        $options = new CsvOptions;
        $options->FIELD_DELIMITER = $this->detectDelimiter($path);

        return new CsvReader($options);
    }

    private function detectDelimiter(string $path): string
    {
        $line = (string) fgets($handle = fopen($path, 'r'));
        fclose($handle);
        $counts = [';' => substr_count($line, ';'), ',' => substr_count($line, ','), "\t" => substr_count($line, "\t")];
        arsort($counts);

        return (string) array_key_first($counts);
    }

    /** @return array<string, int> field => column index */
    private function headerMap(array $cells, array $overrides): array
    {
        $norm = array_map(fn ($c) => PartNumber::normalize(Str::ascii($c)), $cells);
        $map = [];
        foreach ($overrides as $field => $header) {
            $i = array_search(PartNumber::normalize(Str::ascii($header)), $norm, true);
            if ($i !== false) {
                $map[$field] = $i;
            }
        }
        foreach (self::HEADERS as $field => $names) {
            if (isset($map[$field])) {
                continue;
            }
            foreach ($norm as $i => $h) {
                if (in_array($h, $names, true) && ! in_array($i, $map, true)) {
                    $map[$field] = $i;
                    break;
                }
            }
        }

        return $map;
    }
}
