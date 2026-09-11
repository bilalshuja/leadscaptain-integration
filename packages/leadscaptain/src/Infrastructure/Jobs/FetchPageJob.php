<?php

declare(strict_types=1);

namespace Leadscaptain\Infrastructure\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Leadscaptain\Application\Services\LeadService;
use Leadscaptain\Infrastructure\Http\LeadCaptainClient;
use Throwable;

final class FetchPageJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly int $page,
        public readonly int $perPage = 100
    ) {
        $this->onQueue('leads');
    }

    public function handle(LeadCaptainClient $client, LeadService $service): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        try {
            $response = $client->fetchPage($this->page, $this->perPage);
            $rawItems = $response['data'] ?? [];

            $service->processPageData($this->page, $rawItems);
        } catch (Throwable $e) {
            Log::channel('stderr')->error("Failed processing page {$this->page} in queue job", [
                'exception' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [2, 5, 10];
    }
}