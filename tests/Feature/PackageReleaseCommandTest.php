<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use OiLab\OiLaravelDevelopment\Ai\CommitMessageAgent;

/**
 * Run a git command inside the given directory and fail loudly if it errors.
 *
 * @param  array<int, string>  $command
 */
function git(string $path, array $command): string
{
    $result = Process::path($path)->run(array_merge(['git'], $command));

    expect($result->successful())->toBeTrue($result->errorOutput());

    return trim($result->output());
}

beforeEach(function () {
    $this->root = sys_get_temp_dir().'/oi-release-'.uniqid();
    $this->packagesPath = $this->root.'/packages';
    $this->remotePath = $this->root.'/remote.git';
    $this->repoPath = $this->packagesPath.'/demo-package';

    File::ensureDirectoryExists($this->packagesPath);

    // Bare repository acting as "origin".
    File::ensureDirectoryExists($this->remotePath);
    git($this->remotePath, ['init', '--bare', '-b', 'main', '-q']);

    // Working repository with one commit and an initial tag.
    File::ensureDirectoryExists($this->repoPath);
    git($this->repoPath, ['init', '-b', 'main', '-q']);
    git($this->repoPath, ['config', 'user.email', 'test@example.com']);
    git($this->repoPath, ['config', 'user.name', 'Test']);
    git($this->repoPath, ['remote', 'add', 'origin', $this->remotePath]);
    File::put($this->repoPath.'/README.md', "# Demo\n");
    git($this->repoPath, ['add', '-A']);
    git($this->repoPath, ['commit', '-m', 'init', '-q']);
    git($this->repoPath, ['tag', '-a', 'v1.0.0', '-m', 'Release version v1.0.0']);
    git($this->repoPath, ['push', '-q', 'origin', 'main', '--tags']);

    config()->set('oi-laravel-development.release.packages_path', $this->packagesPath);
    config()->set('oi-laravel-development.release.default_bump', 'patch');
    config()->set('oi-laravel-development.release.first_tag', 'v1.0.0');
    config()->set('oi-laravel-development.release.ai.enabled', false);
});

afterEach(function () {
    if (is_dir($this->root)) {
        File::deleteDirectory($this->root);
    }
});

function makeChange(string $repoPath, string $content = 'updated'): void
{
    File::put($repoPath.'/README.md', "# Demo\n{$content}\n");
}

it('commits, bumps the patch version and pushes the tag', function () {
    makeChange($this->repoPath);

    $this->artisan('package:release', [
        'package' => 'demo-package',
        '--message' => 'fix: tweak readme',
        '--yes' => true,
    ])->assertSuccessful();

    expect(git($this->repoPath, ['tag', '--list', 'v1.0.1']))->toBe('v1.0.1')
        ->and(git($this->repoPath, ['log', '-1', '--pretty=%s']))->toBe('fix: tweak readme')
        ->and(git($this->repoPath, ['status', '--porcelain']))->toBe('')
        // Tag and branch landed on the remote.
        ->and(git($this->remotePath, ['tag', '--list', 'v1.0.1']))->toBe('v1.0.1');
});

it('bumps the minor version with --minor', function () {
    makeChange($this->repoPath);

    $this->artisan('package:release', [
        'package' => 'demo-package',
        '--message' => 'feat: add thing',
        '--minor' => true,
        '--yes' => true,
    ])->assertSuccessful();

    expect(git($this->repoPath, ['tag', '--list', 'v1.1.0']))->toBe('v1.1.0');
});

it('uses an explicit tag with --tag', function () {
    makeChange($this->repoPath);

    $this->artisan('package:release', [
        'package' => 'demo-package',
        '--message' => 'feat: jump version',
        '--tag' => 'v2.0.0',
        '--yes' => true,
    ])->assertSuccessful();

    expect(git($this->repoPath, ['tag', '--list', 'v2.0.0']))->toBe('v2.0.0');
});

it('rejects an explicit tag that already exists', function () {
    makeChange($this->repoPath);

    $this->artisan('package:release', [
        'package' => 'demo-package',
        '--message' => 'feat: dup',
        '--tag' => 'v1.0.0',
        '--yes' => true,
    ])->assertFailed();
});

it('rejects conflicting bump flags', function () {
    $this->artisan('package:release', [
        'package' => 'demo-package',
        '--major' => true,
        '--minor' => true,
    ])->expectsOutputToContain('Use only one of')->assertFailed();
});

it('makes no changes in dry-run mode', function () {
    makeChange($this->repoPath);

    $this->artisan('package:release', [
        'package' => 'demo-package',
        '--message' => 'fix: nope',
        '--dry-run' => true,
    ])->assertSuccessful();

    expect(git($this->repoPath, ['tag', '--list', 'v1.0.1']))->toBe('')
        // The working tree is still dirty: nothing was committed.
        ->and(git($this->repoPath, ['status', '--porcelain']))->not->toBe('');
});

it('uses the AI generated message when enabled', function () {
    config()->set('oi-laravel-development.release.ai.enabled', true);
    CommitMessageAgent::fake(['chore: ai generated message']);

    makeChange($this->repoPath);

    $this->artisan('package:release', [
        'package' => 'demo-package',
        '--yes' => true,
    ])->assertSuccessful();

    expect(git($this->repoPath, ['log', '-1', '--pretty=%s']))->toBe('chore: ai generated message');
});

it('fails when the package is not a git repository', function () {
    File::ensureDirectoryExists($this->packagesPath.'/plain');

    $this->artisan('package:release', [
        'package' => 'plain',
        '--message' => 'noop',
        '--yes' => true,
    ])->assertFailed();
});
