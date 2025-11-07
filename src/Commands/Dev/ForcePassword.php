<?php

namespace OiLab\OiLaravelDevelopment\Commands\Dev;

use App\Models\User;
use Illuminate\Console\Command;

class ForcePassword extends Command
{
    protected $signature = 'dev:force-password {email} {--password= : The new password to set}';

    protected $description = 'Force reset a user password for development purposes';

    public function handle(): void
    {
        $user = User::where('email', $this->argument('email'))->firstOrFail();

        $password = $this->option('password') ?: config('oi-laravel-development.default_password');

        $user->update([
            'password' => bcrypt($password),
        ]);

        $this->info("Password updated successfully for {$user->email}");
    }
}
