<?php

declare(strict_types=1);

namespace Leadscaptain\Tests\Feature;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Leadscaptain\Domain\Entities\Lead;
use Leadscaptain\Infrastructure\Repositories\EloquentLeadRepository;
use Tests\TestCase;

final class EloquentLeadRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_upsert_batch_inserts_and_updates_idempotently(): void
    {
        $repository = new EloquentLeadRepository;

        $leadsInitial = [
            new Lead('lead_1', 'lead1@example.com', 'Initial Name', ['score' => 10], new DateTimeImmutable('2026-09-11 10:00:00')),
            new Lead('lead_2', 'lead2@example.com', 'Lead Two', ['score' => 20]),
        ];

        $repository->upsertBatch($leadsInitial);

        $this->assertDatabaseCount('leads', 2);
        $this->assertDatabaseHas('leads', [
            'external_id' => 'lead_1',
            'name' => 'Initial Name',
        ]);

        // Upsert with modified data for lead_1 (must update, not duplicate)
        $leadsUpdated = [
            new Lead('lead_1', 'lead1_updated@example.com', 'Updated Name', ['score' => 50]),
            new Lead('lead_3', 'lead3@example.com', 'Lead Three', ['score' => 30]),
        ];

        $repository->upsertBatch($leadsUpdated);

        $this->assertDatabaseCount('leads', 3);
        $this->assertDatabaseHas('leads', [
            'external_id' => 'lead_1',
            'email' => 'lead1_updated@example.com',
            'name' => 'Updated Name',
        ]);

        $found = $repository->findByExternalId('lead_1');
        $this->assertNotNull($found);
        $this->assertSame('Updated Name', $found->name);
        $this->assertSame(['score' => 50], $found->metadata);
    }
}
