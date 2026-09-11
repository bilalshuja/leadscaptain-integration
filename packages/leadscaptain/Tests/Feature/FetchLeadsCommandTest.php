<?php

declare(strict_types=1);

namespace Leadscaptain\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Leadscaptain\Infrastructure\Jobs\FetchLeadsJob;

final class FetchLeadsCommandTest extends TestCase
{
    public function test_artisan_command_dispatches_master_sync_job_to_leads_queue(): void
    {
        Queue::fake();

        $this->artisan('leadscaptain:fetch-leads')
            ->expectsOutput('Initiating Leadscaptain lead ingestion process...')
            ->expectsOutput('Sync job dispatched successfully to the [leads] queue.')
            ->assertSuccessful();

        Queue::assertPushedOn('leads', FetchLeadsJob::class);
    }
}