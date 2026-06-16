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

    /*
    |--------------------------------------------------------------------------
    | Package Release
    |--------------------------------------------------------------------------
    |
    | Configuration for the package:release command, which commits, tags and
    | pushes a sibling package living in its own Git repository.
    |
    | - packages_path: directory holding the package repositories to release.
    | - default_bump:  version increment applied when no override flag is given
    |                  (one of "patch", "minor", "major").
    | - first_tag:     tag created for a repository that has no tags yet.
    | - ai:            AI-assisted commit message generation (laravel/ai +
    |                  Ollama). Set "enabled" to false to always prompt manually.
    |
    */

    'release' => [

        'packages_path' => env('OI_RELEASE_PACKAGES_PATH', base_path('packages/oi-lab')),

        'default_bump' => env('OI_RELEASE_DEFAULT_BUMP', 'patch'),

        'first_tag' => env('OI_RELEASE_FIRST_TAG', 'v1.0.0'),

        'ai' => [
            'enabled' => env('OI_RELEASE_AI_ENABLED', true),
            'provider' => env('OI_RELEASE_AI_PROVIDER', 'ollama'),
            'model' => env('OI_RELEASE_AI_MODEL', 'qwen3:8b'),
            'timeout' => (int) env('OI_RELEASE_AI_TIMEOUT', 120),

            // Amount of staged diff (in characters) sent to the model. More
            // context lets it write a richer message but is slower to generate.
            'max_diff_chars' => (int) env('OI_RELEASE_AI_MAX_DIFF_CHARS', 12000),

            // Upper bound on body bullet points suggested to the model. Raise it
            // for longer commit messages, lower it for terse ones.
            'max_body_bullets' => (int) env('OI_RELEASE_AI_MAX_BODY_BULLETS', 8),

            // Fully override the agent instructions. When set (non-empty) it
            // replaces the built-in prompt. Leave null for the detailed default.
            'instructions' => env('OI_RELEASE_AI_INSTRUCTIONS'),
        ],

    ],

];
