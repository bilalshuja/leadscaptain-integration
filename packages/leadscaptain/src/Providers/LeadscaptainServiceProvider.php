<?php

declare(strict_types=1);

namespace Leadscaptain\Providers;

use Illuminate\Support\ServiceProvider;

final class LeadscaptainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/leadscaptain.php',
            'leadscaptain'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../Config/leadscaptain.php' => config_path('leadscaptain.php'),
            ], 'leadscaptain-config');
        }
    }
}