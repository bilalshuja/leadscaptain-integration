<?php

declare(strict_types=1);

namespace Leadscaptain\Infrastructure\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Leadscaptain\Domain\Entities\Lead;
use Leadscaptain\Domain\Repositories\LeadRepositoryInterface;

final class EloquentLeadRepository implements LeadRepositoryInterface
{
    /**
     * @param array<Lead> $leads
     */
    public function upsertBatch(array $leads): void
    {
        if (empty($leads)) {
            return;
        }

        $records = array_map(static fn (Lead $lead) => $lead->toDatabaseArray(), $leads);

        // Chunking in batches of 500 to keep memory and SQL payload optimized
        foreach (array_chunk($records, 500) as $chunk) {
            DB::table('leads')->upsert(
                $chunk,
                ['external_id'],
                ['email', 'name', 'metadata', 'remote_created_at', 'updated_at']
            );
        }
    }

    public function findByExternalId(string $externalId): ?Lead
    {
        $record = DB::table('leads')->where('external_id', $externalId)->first();

        if (!$record) {
            return null;
        }

        $createdAt = null;
        if ($record->remote_created_at !== null) {
            $createdAt = new DateTimeImmutable($record->remote_created_at);
        }

        return new Lead(
            externalId: (string) $record->external_id,
            email: $record->email !== null ? (string) $record->email : null,
            name: $record->name !== null ? (string) $record->name : null,
            metadata: json_decode((string) ($record->metadata ?? '{}'), true) ?: [],
            createdAt: $createdAt
        );
    }
}