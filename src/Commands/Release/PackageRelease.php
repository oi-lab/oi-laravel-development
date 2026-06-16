<?php

namespace OiLab\OiLaravelDevelopment\Commands\Release;

use Illuminate\Console\Command;
use OiLab\OiLaravelDevelopment\Services\Release\CommitMessageGenerator;
use OiLab\OiLaravelDevelopment\Support\Release\GitPackage;
use OiLab\OiLaravelDevelopment\Support\Release\Version;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\textarea;

class PackageRelease extends Command
{
    protected $signature = 'package:release
        {package? : Directory name of the package to release (under the packages path)}
        {--m|message= : Commit message to use (skips AI generation)}
        {--major : Bump the major version}
        {--minor : Bump the minor version}
        {--tag= : Use an explicit tag (e.g. v1.2.0) instead of bumping}
        {--all : Release every package that has pending changes}
        {--no-ai : Disable AI commit message generation and prompt manually}
        {--y|yes : Skip confirmation prompts}
        {--dry-run : Print the git commands without executing them}';

    protected $description = 'Commit, tag and push a package to its Git remote';

    public function handle(CommitMessageGenerator $generator): int
    {
        $basePath = (string) config('oi-laravel-development.release.packages_path');

        if (! is_dir($basePath)) {
            $this->error("Packages path not found: {$basePath}");

            return self::FAILURE;
        }

        $bumpLevel = $this->resolveBumpLevel();

        if ($bumpLevel === null) {
            return self::FAILURE;
        }

        $packages = $this->resolvePackages($basePath);

        if ($packages === []) {
            return self::FAILURE;
        }

        $failures = 0;

        foreach ($packages as $package) {
            $this->newLine();
            $this->line("📦 <fg=cyan>{$package->name}</>");

            if (! $this->releasePackage($package, $generator, $bumpLevel)) {
                $failures++;
            }
        }

        $this->newLine();

        if ($failures > 0) {
            $this->error("{$failures} package(s) failed to release.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Validate the version override flags and return the bump level to apply.
     */
    private function resolveBumpLevel(): ?string
    {
        $selected = array_filter([
            'major' => (bool) $this->option('major'),
            'minor' => (bool) $this->option('minor'),
            'tag' => $this->option('tag') !== null,
        ]);

        if (count($selected) > 1) {
            $this->error('Use only one of --major, --minor or --tag.');

            return null;
        }

        if ($this->option('major')) {
            return 'major';
        }

        if ($this->option('minor')) {
            return 'minor';
        }

        return (string) config('oi-laravel-development.release.default_bump', 'patch');
    }

    /**
     * Build the list of packages to release based on the arguments and options.
     *
     * @return array<int, GitPackage>
     */
    private function resolvePackages(string $basePath): array
    {
        if ($this->option('all')) {
            $dirty = array_values(array_filter(
                $this->discoverRepositories($basePath),
                fn (GitPackage $package): bool => $package->hasChanges(),
            ));

            if ($dirty === []) {
                $this->info('No package has pending changes.');
            }

            return $dirty;
        }

        $name = $this->argument('package');

        if ($name === null) {
            $name = $this->promptForPackage($basePath);

            if ($name === null) {
                return [];
            }
        }

        $package = new GitPackage($name, rtrim($basePath, '/').'/'.$name, $this->isDryRun(), $this->logger());

        if (! $package->isRepository()) {
            $this->error("[{$name}] is not a Git repository at {$package->path}.");

            return [];
        }

        return [$package];
    }

    private function promptForPackage(string $basePath): ?string
    {
        $dirty = array_filter(
            $this->discoverRepositories($basePath),
            fn (GitPackage $package): bool => $package->hasChanges(),
        );

        if ($dirty === []) {
            $this->info('No package has pending changes.');

            return null;
        }

        return select(
            label: 'Which package do you want to release?',
            options: array_map(
                fn (GitPackage $package): string => $package->name.'  ('.($package->latestVersion() ?? 'no tag').')',
                array_values($dirty),
            ),
            // The option labels carry the version hint, so map back to the name.
            transform: fn (string $value): string => trim((string) strtok($value, ' ')),
        );
    }

    /**
     * @return array<int, GitPackage>
     */
    private function discoverRepositories(string $basePath): array
    {
        $repositories = [];

        foreach (glob(rtrim($basePath, '/').'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $package = new GitPackage(basename($dir), $dir, $this->isDryRun(), $this->logger());

            if ($package->isRepository() && $package->hasRemote()) {
                $repositories[] = $package;
            }
        }

        return $repositories;
    }

    private function releasePackage(GitPackage $package, CommitMessageGenerator $generator, string $bumpLevel): bool
    {
        try {
            if (! $package->hasRemote()) {
                $this->error('No "origin" remote configured. Skipping.');

                return false;
            }

            $tag = $this->resolveTag($package, $bumpLevel);

            if ($tag === null) {
                return false;
            }

            if (! $package->hasChanges()) {
                $this->warn('No pending changes to commit; only the tag will be created.');
            } else {
                $package->stageAll();

                $message = $this->resolveCommitMessage($package, $generator);

                if ($message === null) {
                    $this->warn('Empty commit message. Skipping.');

                    return false;
                }

                if (! $this->confirmStep("Commit with message:\n\n{$message}\n")) {
                    $this->line('Skipped.');

                    return false;
                }

                $package->commit($message);
                $this->info('✓ Committed.');
            }

            if (! $this->confirmStep("Create and push tag {$tag} to origin?")) {
                $this->line('Tag skipped.');

                return false;
            }

            $package->createTag((string) $tag, "Release version {$tag}");
            $package->push($package->currentBranch());
            $package->push((string) $tag);

            $this->info("✓ Released {$tag}".($this->isDryRun() ? ' (dry-run)' : '').'.');

            return true;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return false;
        }
    }

    /**
     * Resolve the tag to create, either from --tag or by bumping the latest one.
     */
    private function resolveTag(GitPackage $package, string $bumpLevel): ?Version
    {
        if ($this->option('tag') !== null) {
            $explicit = Version::parse((string) $this->option('tag'));

            if ($explicit === null) {
                $this->error('Invalid --tag value. Expected something like v1.2.0.');

                return null;
            }

            if ($package->tagExists((string) $explicit)) {
                $this->error("Tag {$explicit} already exists.");

                return null;
            }

            return $explicit;
        }

        $latest = $package->latestVersion();

        if ($latest === null) {
            $first = Version::parse((string) config('oi-laravel-development.release.first_tag', 'v1.0.0'));
            $this->line("No existing tag. Starting at {$first}.");

            return $first;
        }

        $next = $latest->bump($bumpLevel);
        $this->line("Latest tag {$latest} → {$next} ({$bumpLevel}).");

        return $next;
    }

    private function resolveCommitMessage(GitPackage $package, CommitMessageGenerator $generator): ?string
    {
        if ($this->option('message') !== null) {
            return trim((string) $this->option('message'));
        }

        $this->line($package->stagedDiffSummary(128));

        $suggestion = '';

        if (! $this->option('no-ai') && config('oi-laravel-development.release.ai.enabled')) {
            $diff = $package->stagedDiffSummary(
                (int) config('oi-laravel-development.release.ai.max_diff_chars', 6000),
            );

            $suggestion = (string) spin(
                fn (): ?string => $generator->generate($diff),
                'Generating commit message with '.config('oi-laravel-development.release.ai.model').'…',
            );

            if ($suggestion === '') {
                $reason = $generator->lastError() ?? 'unknown error';
                $this->warn("AI generation unavailable ({$reason}); enter the message manually.");
            }
        }

        if ($this->option('yes')) {
            if ($suggestion === '') {
                $this->error('No commit message available. Provide --message or enable AI.');

                return null;
            }

            $this->line("Using message:\n\n{$suggestion}\n");

            return trim($suggestion);
        }

        return trim(textarea(
            label: 'Commit message',
            default: $suggestion,
            required: true,
            rows: 14,
            hint: 'Review and edit the suggested message before confirming.',
        ));
    }

    private function confirmStep(string $message): bool
    {
        if ($this->option('yes') || $this->isDryRun()) {
            $this->line($message);

            return true;
        }

        return confirm($message);
    }

    private function isDryRun(): bool
    {
        return (bool) $this->option('dry-run');
    }

    /**
     * @return \Closure(string):void
     */
    private function logger(): \Closure
    {
        return function (string $command): void {
            if ($this->isDryRun()) {
                $this->line("  <fg=yellow>[dry-run]</> {$command}");
            }
        };
    }
}
