<?php

it('runs all seeders with force flag', function () {
    config()->set('oi-laravel-development.seeders', [
        'Database\\Seeders\\TestSeeder' => 'Test Seeder',
    ]);

    $this->artisan('init:all', ['--force' => true])
        ->expectsOutput('Seeders executed successfully.')
        ->assertSuccessful();
});

it('shows message when no seeders are configured', function () {
    config()->set('oi-laravel-development.seeders', []);

    $this->artisan('init:all', ['--force' => true])
        ->expectsOutput('No seeders selected.')
        ->assertSuccessful();
});

it('respects configured seeders list', function () {
    $seeders = [
        'Database\\Seeders\\UserSeeder' => 'Users',
        'Database\\Seeders\\RoleSeeder' => 'Roles',
    ];

    config()->set('oi-laravel-development.seeders', $seeders);

    $command = $this->artisan('init:all', ['--force' => true]);

    expect($command->execute())->toBe(0);
});
