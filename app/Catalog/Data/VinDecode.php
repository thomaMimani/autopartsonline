<?php

namespace App\Catalog\Data;

final readonly class VinDecode
{
    /** @param  list<VehicleRef>  $candidates */
    public function __construct(
        public array $candidates,
        public string $decoder,
        public array $raw = [],
    ) {}
}
