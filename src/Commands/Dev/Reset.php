<?php

namespace OiLab\OiLaravelDevelopment\Commands\Dev;

use Illuminate\Console\Command;

class Reset extends Command
{
    protected $signature = 'dev:reset {--F|force : Force to reset DB} {--I|init : Import initial records}';

    protected $description = 'Reset database and clear all caches for development';

    public function handle(): void
    {
        if ($this->option('force')) {
            $this->process();

            return;
        }

        if ($this->confirm('Are you sure you want to reset the database?')) {
            $this->process();

            return;
        }
    }

    protected function process(): void
    {
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');

        $this->call('dev:clear-storage', ['--force' => true]);

        $this->info('All caches cleaned.');

        $this->call('migrate:fresh');
        $this->call('db:seed');

        if ($this->option('init')) {
            $this->call('init:all');
        }

        $this->info('Database reset successfully.');
    }
}
