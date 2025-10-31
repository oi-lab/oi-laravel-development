<?php

it('requires confirmation without force flag', function () {
    $this->artisan('dev:reset')
        ->expectsConfirmation('Are you sure you want to reset the database?', 'no')
        ->assertSuccessful();
});

it('processes reset with force flag', function () {
    $this->artisan('dev:reset', ['--force' => true])
        ->expectsOutput('All caches cleaned.')
        ->expectsOutput('Database reset successfully.')
        ->assertSuccessful();
});

it('calls init command when init flag is provided', function () {
    $this->artisan('dev:reset', ['--force' => true, '--init' => true])
        ->assertSuccessful();
});
