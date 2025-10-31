<?php

namespace OiLab\LaravelDevelopment\Commands\Dev;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

use function Laravel\Prompts\confirm;

class ClearStorage extends Command
{
    protected $signature = 'dev:clear-storage {--F|--force}';

    protected $description = 'Clear storage directories except configured exceptions';

    protected array $directories = [];

    public function handle(): void
    {
        $forceOption = $this->option('force');
        $exceptions = config('oi-development.storage_exceptions', []);

        $disk = config('app.env') === 'production' ? 's3' : 'local';
        $this->directories = Storage::disk($disk)->directories('.');

        if ($forceOption) {
            $this->process($disk, $exceptions);

            return;
        }

        $list = collect($this->directories)->filter(fn ($directory) => ! in_array($directory, $exceptions));
        if ($list->count() > 5) {
            $hint = $list->take(5)->join(', ').' and '.$list->count() - 5 .' more';
        } else {
            $hint = $list->join(', ');
        }

        $confirmed = confirm(
            label: 'Are you sure to delete these directories?',
            default: false,
            yes: 'I confirm',
            no: 'I decline',
            hint: $hint
        );

        if (! $confirmed) {
            return;
        }

        $this->process($disk, $exceptions);
    }

    protected function process(string $disk, array $exceptions): void
    {
        foreach ($this->directories as $directory) {
            if (! in_array($directory, $exceptions)) {
                Storage::disk($disk)->deleteDirectory($directory);
            }
        }

        $this->info('Storage cleared');
    }
}
