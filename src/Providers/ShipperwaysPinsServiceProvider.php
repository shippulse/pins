<?php

namespace Shipperways\Pins\Providers;

use Illuminate\Support\ServiceProvider;
use Shipperways\Pins\Console\Commands\ImportPinsCommand;

class ShipperwaysPinsServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {}

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        // Register the command if we are using the application via the CLI
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportPinsCommand::class,
            ]);
        }

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
