<?php

namespace OiLab\OiLaravelDevelopment\Commands\Init;

use Illuminate\Console\Command;

use function Laravel\Prompts\multiselect;

class InitAll extends Command
{
    protected $signature = 'init:all {--F|force : Run all seeders without prompting}';

    protected $description = 'Run selected database seeders';

    public function handle(): void
    {
        $seeders = $this->getAvailableSeeders();

        $selectedSeeders = $this->option('force')
            ? array_keys($seeders)
            : multiselect(
                label: 'Select seeders to run:',
                options: $seeders,
                default: array_keys($seeders),
            );

        if (empty($selectedSeeders)) {
            $this->info('No seeders selected.');

            return;
        }

        foreach ($selectedSeeders as $seeder) {
            $this->call('db:seed', ['--class' => $seeder]);
        }

        $this->info('Seeders executed successfully.');
    }

    /**
     * Get available seeders in order.
     *
     * @return array<string, string>
     */
    protected function getAvailableSeeders(): array
    {
        return config('oi-laravel-development.seeders', []);
    }
}
