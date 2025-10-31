<?php

use Illuminate\Support\Facades\File;

it('clears configured log files', function () {
    $logPath = storage_path('logs/laravel.log');

    File::ensureDirectoryExists(storage_path('logs'));
    File::put($logPath, 'test log content');

    expect(File::exists($logPath))->toBeTrue();

    $this->artisan('dev:clear-log')
        ->expectsOutput('Clearing log files...')
        ->expectsOutputToContain("Deleted: {$logPath}")
        ->expectsOutput('Log files cleared successfully.')
        ->assertSuccessful();

    expect(File::exists($logPath))->toBeFalse();
});

it('warns when log file does not exist', function () {
    $this->artisan('dev:clear-log')
        ->expectsOutput('Clearing log files...')
        ->assertSuccessful();
});
