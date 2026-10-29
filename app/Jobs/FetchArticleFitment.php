<?php

namespace App\Jobs;

use App\Services\FitmentService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchArticleFitment implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(public int $articleId) {}

    public function uniqueId(): string
    {
        return (string) $this->articleId;
    }

    public function handle(FitmentService $fitment): void
    {
        $fitment->fetch($this->articleId);
    }
}
