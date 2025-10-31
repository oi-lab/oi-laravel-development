<?php

namespace OiLab\LaravelDevelopment;

use Illuminate\Support\ServiceProvider;
use OiLab\LaravelDevelopment\Commands\Dev\ClearLog;
use OiLab\LaravelDevelopment\Commands\Dev\ClearStorage;
use OiLab\LaravelDevelopment\Commands\Dev\ForcePassword;
use OiLab\LaravelDevelopment\Commands\Dev\Reset;
use OiLab\LaravelDevelopment\Commands\Init\InitAll;

class OiLaravelDevelopmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/oi-development.php',
            'oi-development'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/oi-development.php' => config_path('oi-development.php'),
            ], 'laravel-development-config');

            $this->commands([
                ClearLog::class,
                ClearStorage::class,
                ForcePassword::class,
                Reset::class,
                InitAll::class,
            ]);
        }
    }
}
