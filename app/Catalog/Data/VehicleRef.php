<?php

namespace App\Catalog\Data;

final readonly class VehicleRef
{
    public function __construct(
        public int $vehicleId,
        public ?string $manufacturer = null,
        public ?string $model = null,
        public ?string $name = null,
        public ?int $kw = null,
        public ?int $hp = null,
        public ?int $ccm = null,
        public ?string $fuel = null,
        public ?int $yearFrom = null,
        public ?int $yearTo = null,
        public int $typeId = 1,
    ) {}
}
