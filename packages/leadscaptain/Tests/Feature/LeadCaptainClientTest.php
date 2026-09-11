<?php

declare(strict_types=1);

namespace Leadscaptain\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Leadscaptain\Infrastructure\Http\LeadCaptainClient;
use RuntimeException;

final class LeadCaptainClientTest extends TestCase

{

    protected function setUp(): void
    {
        parent::setUp();
        
        try {
            Redis::del('leadscaptain_api_throttle');
        } catch (\Throwable) {
          
        }
    }


    public function test_fetch_page_handles_429_adaptive_retry_successfully(): void
    {
        Http::fake([
            'https://api.leadscaptain.com/leads*' => Http::sequence()
                ->push(['error' => 'Too Many Requests'], 429)
                ->push([
                    'data' => [
                        ['id' => 'lead_100', 'email' => 'client@leadscaptain.com', 'name' => 'Lead Client']
                    ],
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 50,
                        'total' => 1,
                    ],
                ], 200),
        ]);

        $client = new LeadCaptainClient(
            baseUrl: 'https://api.leadscaptain.com',
            apiKey: 'test-api-key',
            timeout: 5,
            retryAttempts: 2
        );

        $result = $client->fetchPage(1);

        $this->assertArrayHasKey('data', $result);
        $this->assertSame('lead_100', $result['data'][0]['id']);
        Http::assertSentCount(2);
    }

    public function test_fetch_pages_concurrently_pools_requests_with_concurrency_limit(): void
    {
        Http::fake([
            'https://api.leadscaptain.com/leads?page=2&per_page=100' => Http::response([
                'data' => [['id' => 'lead_p2']]
            ], 200),
            'https://api.leadscaptain.com/leads?page=3&per_page=100' => Http::response([
                'data' => [['id' => 'lead_p3']]
            ], 200),
        ]);

        $client = new LeadCaptainClient(
            baseUrl: 'https://api.leadscaptain.com',
            apiKey: 'test-api-key',
            concurrency: 2
        );

        $results = $client->fetchPagesConcurrently([2, 3]);

        $this->assertCount(2, $results);
        $this->assertArrayHasKey(2, $results);
        $this->assertArrayHasKey(3, $results);
    }

    public function test_fetch_page_throws_runtime_exception_on_unhandled_failure(): void
    {
        Http::fake([
            'https://api.leadscaptain.com/leads*' => Http::response(['error' => 'Server Error'], 500),
        ]);

        $client = new LeadCaptainClient(
            baseUrl: 'https://api.leadscaptain.com',
            apiKey: 'test-api-key',
            timeout: 5,
            retryAttempts: 1
        );

        $this->expectException(RuntimeException::class);
        $client->fetchPage(1);
    }

    public function test_fetch_page_blocks_when_redis_rate_limit_exceeded(): void
    {
        Http::fake([
            'https://api.leadscaptain.com/leads*' => Http::response(['data' => []], 200),
        ]);

        
        $client = new LeadCaptainClient(
            baseUrl: 'https://api.leadscaptain.com',
            apiKey: 'test-api-key',
            timeout: 5,
            retryAttempts: 1,
            rateLimit: 1
        );

       
        $first = $client->fetchPage(1);
        $this->assertIsArray($first);

        // 2nd request immediately triggers Redis throttle circuit break
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Leadscaptain API rate limit exceeded in Redis throttle.');

        $client->fetchPage(2);
    }
}