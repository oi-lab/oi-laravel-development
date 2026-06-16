<?php

namespace OiLab\OiLaravelDevelopment;

use Illuminate\Support\ServiceProvider;
use OiLab\OiLaravelDevelopment\Commands\Dev\ClearLog;
use OiLab\OiLaravelDevelopment\Commands\Dev\ClearStorage;
use OiLab\OiLaravelDevelopment\Commands\Dev\ForcePassword;
use OiLab\OiLaravelDevelopment\Commands\Dev\Reset;
use OiLab\OiLaravelDevelopment\Commands\Init\InitAll;
use OiLab\OiLaravelDevelopment\Commands\Release\PackageRelease;
use OiLab\OiLaravelDevelopment\Commands\Skills\InstallSkills;

class OiLaravelDevelopmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/oi-laravel-development.php',
            'oi-laravel-development'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/oi-laravel-development.php' => config_path('oi-laravel-development.php'),
            ], ['config', 'oi-laravel-development-config']);

            $this->commands([
                ClearLog::class,
                ClearStorage::class,
                ForcePassword::class,
                Reset::class,
                InitAll::class,
                InstallSkills::class,
                PackageRelease::class,
            ]);
        }
    }
}
