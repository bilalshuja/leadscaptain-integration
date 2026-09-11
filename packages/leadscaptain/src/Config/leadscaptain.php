<?php

declare(strict_types=1);

return [
    'base_url' => env('LEADSCAPTAIN_BASE_URL', 'https://api.leadscaptain.com'),
    'api_key' => env('LEADSCAPTAIN_API_KEY', ''),
    'timeout' => (int) env('LEADSCAPTAIN_TIMEOUT', 15),
    'retry_times' => (int) env('LEADSCAPTAIN_RETRY_TIMES', 3),
    'concurrency' => (int) env('LEADSCAPTAIN_CONCURRENCY', 10),
    'per_page' => (int) env('LEADSCAPTAIN_PER_PAGE', 100),
    'rate_limit' => [
        'max_attempts' => (int) env('LEADSCAPTAIN_RATE_LIMIT', 60),
        'decay_seconds' => 60,
    ],
    'channel' => env('LEADSCAPTAIN_LOG_CHANNEL', 'stderr'),
];