<?php

namespace App\Jobs;

use App\Services\MatchingService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MatchPart implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(
        public int $brandId,
        public string $normNo,
        public ?string $rawNo = null,
        public ?string $ean = null,
        public bool $force = false,
    ) {}

    public function uniqueId(): string
    {
        return $this->brandId.':'.$this->normNo;
    }

    public function handle(MatchingService $matching): void
    {
        $matching->match($this->brandId, $this->normNo, $this->rawNo, $this->ean, $this->force);
    }
}
