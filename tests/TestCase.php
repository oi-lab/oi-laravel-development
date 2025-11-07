<?php

namespace OiLab\LaravelDevelopment\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use OiLab\LaravelDevelopment\OiLaravelDevelopmentServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            OiLaravelDevelopmentServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('oi-laravel-development.default_password', 'test-password');
        config()->set('oi-laravel-development.storage_exceptions', ['dev', 'seeders', 'backups']);
        config()->set('oi-laravel-development.log_files', ['laravel.log']);
        config()->set('oi-laravel-development.seeders', []);
    }
}
