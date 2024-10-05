<?php

namespace Davidvandertuijn\LaravelAfterSeeders;

use Davidvandertuijn\LaravelAfterSeeders\app\Console\Commands\AfterSeeders\Deploy as DeployCommand;
use Davidvandertuijn\LaravelAfterSeeders\app\Console\Commands\AfterSeeders\Make as MakeCommand;
use Davidvandertuijn\LaravelAfterSeeders\app\Console\Commands\AfterSeeders\Placeholder as PlaceholderCommand;
use Davidvandertuijn\LaravelAfterSeeders\app\Console\Commands\AfterSeeders\Seed as SeedCommand;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register services.
     */
    public function register() {}

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // Config

        $this->publishes([
            __DIR__.'/config/after_seeders.php' => config_path('after_seeders.php'),
        ]);

        // Create Directory

        if (Config::get('after_seeders.path')
        && ! File::exists(Config::get('after_seeders.path'))) {
            File::makeDirectory(Config::get('after_seeders.path'), 0775, true);
        }

        // Migrations
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // Commands

        $this->commands([
            DeployCommand::class,
            MakeCommand::class,
            PlaceholderCommand::class,
            SeedCommand::class,
        ]);
    }
}
