<?php

declare(strict_types=1);

namespace Leadscaptain\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Leadscaptain\Application\Services\LeadService;

final class FetchLeadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 180;

    public function __construct()
    {
        $this->onQueue('leads');
    }

    public function handle(LeadService $service): void
    {
        $service->orchestrateSync();
    }
}