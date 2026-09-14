<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Leadscaptain\Application\Services\LeadService;
use Tests\TestCase;

class LeadSyncIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        try {
            Redis::flushall();
        } catch (\Throwable $e) {
           
        }

       
        $this->artisan('migrate', [
            '--path' => 'packages/leadscaptain/database/migrations',
            '--realpath' => false,
        ]);
    }

    public function test_it_successfully_syncs_and_upserts_leads_from_api_contract(): void
    {
        $mockPayload = [
            'data' => [
                [
                    'key' => '1cc7b08267a9170ebacfaba140474dd0',
                    'leadscaptain_public_identifier' => 'sunitapermaul',
                    'leadscaptain_id' => '',
                    'leadscaptain_member_id' => 433528474,
                    'emails' => ['sunita.permaul@google.com'],
                    'first_name' => 'Sunita',
                    'last_name' => 'Permaul',
                    'country_code' => '',
                    'position_title' => 'Director Of Logistics',
                    'company_name' => 'The Meehan Consulting Group, Inc.',
                    'skills' => [],
                    'email_status' => 'unchecked',
                ],
                [
                    'key' => '615cf36721fbd7e708411dbab0e2281e',
                    'leadscaptain_public_identifier' => 'marcin-siorek-b6203a8b',
                    'leadscaptain_id' => 'ACoAABMMbPoB1Rj-C1RxCH8KpvsVCda9jTxk1oE',
                    'leadscaptain_member_id' => 319581434,
                    'emails' => ['marcin.siorek@google.ai', 'marcin.siorek@google.com'],
                    'first_name' => 'Marcin',
                    'last_name' => 'Siorek',
                    'gender' => 'male',
                    'country_code' => 'PL',
                    'position_title' => 'Projektant',
                    'company_name' => 'WSK PZL Rzeszow S.A.',
                    'skills' => ['Biomatematyka', 'CAD'],
                    'email_status' => 'unchecked',
                ],
            ],
            'page' => 1,
            'limit' => 2,
            'total' => 2,
            'total_pages' => 1,
        ];

        Http::fake([
            'https://api.leadscaptain.com/*' => Http::response($mockPayload, 200),
        ]);

        $service = app(LeadService::class);
        $service->orchestrateSync();

        $this->assertDatabaseHas('leads', [
            'external_id' => '1cc7b08267a9170ebacfaba140474dd0',
            'company_name' => 'The Meehan Consulting Group, Inc.',
            'position_title' => 'Director Of Logistics',
            'email' => 'sunita.permaul@google.com',
        ]);

        $this->assertDatabaseHas('leads', [
            'external_id' => '615cf36721fbd7e708411dbab0e2281e',
            'company_name' => 'WSK PZL Rzeszow S.A.',
            'position_title' => 'Projektant',
            'country_code' => 'PL',
        ]);
    }
}