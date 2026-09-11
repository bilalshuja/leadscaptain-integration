<?php

declare(strict_types=1);

namespace Leadscaptain\Providers;

use Illuminate\Support\ServiceProvider;
use Leadscaptain\Application\Services\LeadService;
use Leadscaptain\Application\UseCases\FetchAllLeadsUseCase;
use Leadscaptain\Console\Commands\FetchLeadsCommand;
use Leadscaptain\Domain\Repositories\LeadRepositoryInterface;
use Leadscaptain\Infrastructure\Http\LeadCaptainClient;
use Leadscaptain\Infrastructure\Repositories\EloquentLeadRepository;

final class LeadscaptainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/leadscaptain.php',
            'leadscaptain'
        );

        $this->app->bind(LeadRepositoryInterface::class, EloquentLeadRepository::class);

        $this->app->singleton(LeadCaptainClient::class, function () {
            return new LeadCaptainClient(
                baseUrl: (string) config('leadscaptain.base_url'),
                apiKey: (string) config('leadscaptain.api_key'),
                timeout: (int) config('leadscaptain.timeout', 15),
                retryAttempts: (int) config('leadscaptain.retry_times', 3),
                concurrency: (int) config('leadscaptain.concurrency', 10)
            );
        });

        $this->app->singleton(LeadService::class);
        $this->app->singleton(FetchAllLeadsUseCase::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                FetchLeadsCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../Config/leadscaptain.php' => config_path('leadscaptain.php'),
            ], 'leadscaptain-config');

            $this->publishes([
                __DIR__ . '/../../database/migrations/' => database_path('migrations'),
            ], 'leadscaptain-migrations');
        }
    }
}