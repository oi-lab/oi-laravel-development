<?php

use OiLab\OiLaravelDevelopment\Ai\CommitMessageAgent;

it('builds detailed default instructions honouring the bullet budget', function () {
    config()->set('oi-laravel-development.release.ai.instructions', null);
    config()->set('oi-laravel-development.release.ai.max_body_bullets', 6);

    $instructions = (new CommitMessageAgent)->instructions();

    expect($instructions)->toContain('Conventional Commits')
        ->and($instructions)->toContain('up to 6 bullet points')
        ->and($instructions)->toContain('blank line');
});

it('uses the configured instructions override verbatim', function () {
    config()->set('oi-laravel-development.release.ai.instructions', 'Write a haiku about the diff.');

    expect((new CommitMessageAgent)->instructions())->toBe('Write a haiku about the diff.');
});
