<?php

namespace App\Services;

use Illuminate\Support\Collection;

final readonly class VinResult
{
    public const OK = 'ok';                 // exactly one vehicle

    public const AMBIGUOUS = 'ambiguous';   // 2+ vehicles, customer chooses

    public const NOT_FOUND = 'not_found';   // guided picker

    public const INVALID = 'invalid';       // show where to find the VIN

    public function __construct(
        public string $status,
        public string $vin,
        public bool $corrected = false,
        public Collection $vehicles = new Collection,
        public bool $fromCache = false,
        public array $raw = [],
    ) {}
}
