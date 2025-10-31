<?php

namespace OiLab\LaravelDevelopment\Commands\Dev;

use Illuminate\Console\Command;

class ClearLog extends Command
{
    protected $signature = 'dev:clear-log';

    protected $description = 'Clear internal log files';

    public function handle(): void
    {
        $this->info('Clearing log files...');

        $logFiles = collect(config('oi-development.log_files'))
            ->map(fn ($file) => storage_path("logs/{$file}"));

        foreach ($logFiles as $logFile) {
            if (file_exists($logFile)) {
                unlink($logFile);
                $this->info("Deleted: {$logFile}");
            } else {
                $this->warn("File not found: {$logFile}");
            }
        }

        $this->info('Log files cleared successfully.');
    }
}
