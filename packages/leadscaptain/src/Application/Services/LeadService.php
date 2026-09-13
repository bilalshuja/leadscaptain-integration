<?php

declare(strict_types=1);

namespace Leadscaptain\Application\Services;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Leadscaptain\Application\DTOs\LeadData;
use Leadscaptain\Domain\Events\LeadsFetched;
use Leadscaptain\Domain\Repositories\LeadRepositoryInterface;
use Leadscaptain\Infrastructure\Http\LeadCaptainClient;
use Leadscaptain\Infrastructure\Jobs\FetchPageJob;

final class LeadService
{
    public function __construct(
        private readonly LeadCaptainClient $client,
        private readonly LeadRepositoryInterface $repository
    ) {}

    public function orchestrateSync(): void
    {
        $perPage = (int) config('leadscaptain.per_page', 100);

        // Step 1: Discover total pages via page 1
        $pageOne = $this->client->fetchPage(1, $perPage);
        $this->processPageData(1, $pageOne['data'] ?? []);

        $lastPage = (int) ($pageOne['meta']['last_page'] ?? 1);
        Cache::put('leadscaptain_total_pages', $lastPage, now()->addMinutes(30));

        if ($lastPage <= 1) {
            return;
        }

        // parallel requests for pages 2..N via Horizon Batching
        $jobs = [];
        for ($page = 2; $page <= $lastPage; $page++) {
            $jobs[] = new FetchPageJob($page, $perPage);
        }

        Bus::batch($jobs)
            ->name('leadscaptain-concurrent-sync')
            ->allowFailures()
            ->onQueue('leads')
            ->dispatch();
    }

    public function processPageData(int $page, array $rawItems): void
    {
        $entities = [];
        foreach ($rawItems as $item) {
            $dto = LeadData::fromArray($item);
            $entities[] = $dto->toDomain();
        }

        if (! empty($entities)) {
            $this->repository->upsertBatch($entities);
        }

        event(new LeadsFetched(page: $page, count: count($entities)));
    }
}
