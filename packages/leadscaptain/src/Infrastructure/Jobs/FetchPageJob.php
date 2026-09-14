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
use Illuminate\Support\Facades\Notification;
use Leadscaptain\Application\Services\LeadService;
use Leadscaptain\Infrastructure\Http\LeadCaptainClient;
use Leadscaptain\Infrastructure\Notifications\LeadFetchFailedNotification;
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

    public function failed(Throwable $exception): void
    {
        $logChannel = (string) config('leadscaptain.channel', config('leadscaptain.log_channel', 'leadscaptain'));
        $batchId = $this->batchId ?? $this->batch()?->id;

        Log::channel($logChannel)->critical("Page {$this->page} permanently failed after all retries.", [
            'page' => $this->page,
            'batch_id' => $batchId,
            'error' => $exception->getMessage(),
        ]);

        $this->batch()?->cancel();

        $alertEmail = (string) config('leadscaptain.alert_email', env('MAIL_FROM_ADDRESS'));

        Notification::route('mail', $alertEmail)
            ->notify(new LeadFetchFailedNotification(
                page: $this->page,
                errorMessage: $exception->getMessage(),
                batchId: $batchId
            ));
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [2, 5, 10];
    }
}
