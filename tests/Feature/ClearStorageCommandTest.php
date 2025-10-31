<?php

use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

it('clears storage directories except exceptions with force flag', function () {
    Storage::disk('local')->makeDirectory('test-dir');
    Storage::disk('local')->makeDirectory('dev');
    Storage::disk('local')->put('test-dir/file.txt', 'content');

    expect(Storage::disk('local')->exists('test-dir'))->toBeTrue();

    $this->artisan('dev:clear-storage', ['--force' => true])
        ->expectsOutput('Storage cleared')
        ->assertSuccessful();

    expect(Storage::disk('local')->exists('test-dir'))->toBeFalse();
    expect(Storage::disk('local')->exists('dev'))->toBeTrue();
});

it('respects configured storage exceptions', function () {
    Storage::disk('local')->makeDirectory('backups');
    Storage::disk('local')->makeDirectory('seeders');
    Storage::disk('local')->makeDirectory('regular-dir');

    $this->artisan('dev:clear-storage', ['--force' => true])
        ->assertSuccessful();

    expect(Storage::disk('local')->exists('backups'))->toBeTrue();
    expect(Storage::disk('local')->exists('seeders'))->toBeTrue();
    expect(Storage::disk('local')->exists('regular-dir'))->toBeFalse();
});
