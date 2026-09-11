<?php

declare(strict_types=1);

namespace Leadscaptain\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Leadscaptain\Infrastructure\Jobs\FetchLeadsJob;

final class LeadSyncControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_trigger_sync_endpoint_returns_accepted_and_dispatches_job(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/leadscaptain/sync');

        $response->assertStatus(202)
            ->assertJson([
                'status' => 'success',
                'message' => 'Sync queued successfully on [leads] queue.',
            ]);

        Queue::assertPushedOn('leads', FetchLeadsJob::class);
    }

    public function test_webhook_endpoint_upserts_lead_successfully(): void
    {
        $payload = [
            'event' => 'lead.created',
            'data' => [
                'id' => 'webhook_lead_123',
                'email' => 'webhook@leadscaptain.com',
                'name' => 'Bilal Shuja',
                'metadata' => ['plan' => 'enterprise'],
                'created_at' => '2026-09-11T12:00:00+00:00',
            ],
        ];

        $response = $this->postJson('/api/leadscaptain/webhook', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Lead processed and upserted successfully.',
            ]);

        $this->assertDatabaseHas('leads', [
            'external_id' => 'webhook_lead_123',
            'email' => 'webhook@leadscaptain.com',
            'name' => 'Bilal Shuja',
        ]);
    }

    public function test_webhook_endpoint_validates_required_payload(): void
    {
        $response = $this->postJson('/api/leadscaptain/webhook', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['event', 'data']);
    }
}