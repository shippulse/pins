<?php

namespace Obelaw\Shippulse\Pins\Providers;

use Illuminate\Support\ServiceProvider;
use Obelaw\Shippulse\Pins\Console\Commands\ImportMapperCommand;
use Obelaw\Shippulse\Pins\Console\Commands\ImportPinsCommand;

class ShippulsePinsServiceProvider extends ServiceProvider
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
                ImportMapperCommand::class
            ]);
        }

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
