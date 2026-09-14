<?php

declare(strict_types=1);

namespace Leadscaptain\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class LeadsFetched
{
    use Dispatchable;

    public function __construct(
        public int $page,
        public int $count,
        public array $meta = []
    ) {}
}
