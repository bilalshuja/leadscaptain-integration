<?php

declare(strict_types=1);

namespace Leadscaptain\Application\UseCases;

use Leadscaptain\Infrastructure\Jobs\FetchLeadsJob;

final readonly class FetchAllLeadsUseCase
{
    public function execute(): void
    {

        FetchLeadsJob::dispatch()->onQueue('leads');
    }
}
