<?php

declare(strict_types=1);

namespace Leadscaptain\Console\Commands;

use Illuminate\Console\Command;
use Leadscaptain\Application\UseCases\FetchAllLeadsUseCase;

final class FetchLeadsCommand extends Command
{
    protected $signature = 'leadscaptain:fetch-leads {--sync : Run immediately in the foreground instead of queuing}';

    protected $description = 'Fetch all leads from Leadscaptain API with concurrent pagination and bulk upsert';

    public function handle(FetchAllLeadsUseCase $useCase): int
    {
        $this->info('Initiating Leadscaptain lead ingestion process...');

        $useCase->execute();

        $this->info('Sync job dispatched successfully to the [leads] queue.');
        $this->comment('Monitor processing progress live via Laravel Horizon at: /horizon');

        return self::SUCCESS;
    }
}