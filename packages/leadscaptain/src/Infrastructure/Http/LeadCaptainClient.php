<?php

declare(strict_types=1);

namespace Leadscaptain\Infrastructure\Http;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class LeadCaptainClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $timeout = 15,
        private readonly int $retryAttempts = 3,
        private readonly int $concurrency = 10
    ) {}

    
    public function fetchPage(int $page, int $perPage = 100): array
    {
        $startTime = microtime(true);
        $url = rtrim($this->baseUrl, '/') . '/leads';

        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'Accept' => 'application/json',
        ])
        ->timeout($this->timeout)
        ->retry( $this->retryAttempts,
            function (int $attempt) {
                   return match ($attempt) {
                    1 => 1000,
                    2 => 2000,
                    default => 4000,
                };
            },
            function (Throwable $exception) {
                if ($exception instanceof RequestException) {
                    $status = $exception->response->status();
                    return $status === 429 || $status >= 500;
                }
                return true;
            }
        )
        ->get($url, [
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $duration = round(microtime(true) - $startTime, 4);

        Log::channel('stderr')->info("Leadscaptain API request", [
            'page' => $page,
            'status' => $response->status(),
            'duration' => "{$duration}s",
        ]);

        if (!$response->successful()) {
            throw new RuntimeException("API error on page {$page}: HTTP {$response->status()}");
        }

        return $response->json();
    }

    /**
     * using Http::pool

     */
    
    public function fetchPagesConcurrently(array $pages, int $perPage = 100): array
    {
        $chunks = array_chunk($pages, $this->concurrency);
        $allResults = [];

        foreach ($chunks as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk, $perPage) {
                return array_map(function (int $page) use ($pool, $perPage) {
                    return $pool
                        ->withHeaders([
                            'X-API-Key' => $this->apiKey,
                            'Accept' => 'application/json',
                        ])
                        ->timeout($this->timeout)
                        ->get(rtrim($this->baseUrl, '/') . '/leads', [
                            'page' => $page,
                            'per_page' => $perPage,
                        ]);
                }, $chunk);
            });

            foreach ($responses as $index => $response) {
                $pageNum = $chunk[$index] ?? null;

                if ($pageNum !== null && $response instanceof Response && $response->successful()) {
                    $allResults[$pageNum] = $response->json();
                } else {
                    $statusCode = $response instanceof Response ? $response->status() : 'unknown';
                    Log::channel('stderr')->error("Failed concurrent fetch for page {$pageNum}", [
                        'status' => $statusCode,
                    ]);
                }
            }
        }

        return $allResults;
    }
}