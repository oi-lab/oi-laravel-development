<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
        $table->id();
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamps();
    });
});

it('resets user password with default password', function () {
    $user = new class extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'users';
        protected $fillable = ['email', 'password'];
    };

    $user::create([
        'email' => 'test@example.com',
        'password' => bcrypt('old-password'),
    ]);

    $this->artisan('dev:force-password', ['email' => 'test@example.com'])
        ->expectsOutputToContain('Password updated successfully')
        ->assertSuccessful();

    $updatedUser = $user::where('email', 'test@example.com')->first();
    expect(Hash::check('test-password', $updatedUser->password))->toBeTrue();
});

it('resets user password with custom password', function () {
    $user = new class extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'users';
        protected $fillable = ['email', 'password'];
    };

    $user::create([
        'email' => 'test@example.com',
        'password' => bcrypt('old-password'),
    ]);

    $this->artisan('dev:force-password', [
        'email' => 'test@example.com',
        '--password' => 'custom-password',
    ])->assertSuccessful();

    $updatedUser = $user::where('email', 'test@example.com')->first();
    expect(Hash::check('custom-password', $updatedUser->password))->toBeTrue();
});
