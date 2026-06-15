<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Development Password
    |--------------------------------------------------------------------------
    |
    | This password is used by the dev:force-password command when no
    | password is explicitly provided. This allows quick password resets
    | during development.
    |
    */

    'default_password' => env('DEV_DEFAULT_PASSWORD', 'password'),

    /*
    |--------------------------------------------------------------------------
    | Storage Clear Exceptions
    |--------------------------------------------------------------------------
    |
    | These directories will be excluded when running the dev:clear-storage
    | command. Add any directories that should never be deleted.
    |
    */

    'storage_exceptions' => [
        'dev',
        'seeders',
        'backups',
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Files to Clear
    |--------------------------------------------------------------------------
    |
    | List of log files that will be deleted when running the dev:clear-log
    | command. Add any custom log files you want to include.
    |
    */

    'log_files' => [
        'laravel.log',
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Seeders
    |--------------------------------------------------------------------------
    |
    | Define the seeders that will be available in the init:all command.
    | The key is the fully qualified class name, and the value is the
    | display name shown in the selection prompt.
    |
    */

    'seeders' => [
        'Database\\Seeders\\UserSeeder' => 'UserSeeder',
    ],

    /*
    |--------------------------------------------------------------------------
    | Additional Skill Package Paths
    |--------------------------------------------------------------------------
    |
    | The oi:skills command discovers AI assistant skills by scanning every
    | installed oi-lab/* Composer package for an "extra.oi-lab.skills" block.
    | Add absolute paths to extra package roots (e.g. local monorepo packages
    | not resolved through Composer) to include them in the discovery.
    |
    */

    'skill_paths' => [],

];
