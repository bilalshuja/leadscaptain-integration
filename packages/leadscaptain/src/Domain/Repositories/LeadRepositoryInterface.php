<?php

declare(strict_types=1);

namespace Leadscaptain\Domain\Repositories;

use Leadscaptain\Domain\Entities\Lead;

interface LeadRepositoryInterface
{
    /**
     * @param array<Lead> $leads
     */
    public function upsertBatch(array $leads): void;

    public function findByExternalId(string $externalId): ?Lead;
}