<?php

namespace OiLab\OiLaravelDevelopment\Support\Release;

use Closure;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class GitPackage
{
    /**
     * @param  Closure(string):void|null  $logger  Receives every mutating command before it runs.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        private readonly bool $dryRun = false,
        private readonly ?Closure $logger = null,
    ) {}

    public function isRepository(): bool
    {
        return is_dir($this->path.'/.git');
    }

    public function hasRemote(string $remote = 'origin'): bool
    {
        $remotes = preg_split('/\R/', $this->read(['git', 'remote']), -1, PREG_SPLIT_NO_EMPTY);

        return in_array($remote, $remotes ?: [], true);
    }

    public function currentBranch(): string
    {
        return $this->read(['git', 'rev-parse', '--abbrev-ref', 'HEAD']);
    }

    public function status(): string
    {
        return $this->read(['git', 'status', '--porcelain']);
    }

    public function hasChanges(): bool
    {
        return $this->status() !== '';
    }

    public function stageAll(): void
    {
        $this->write(['git', 'add', '-A']);
    }

    /**
     * A compact summary of the staged changes, suitable for feeding to an LLM.
     */
    public function stagedDiffSummary(int $maxChars = 6000): string
    {
        $stat = $this->read(['git', 'diff', '--cached', '--stat']);
        $patch = $this->read(['git', 'diff', '--cached']);

        if (mb_strlen($patch) > $maxChars) {
            $patch = mb_substr($patch, 0, $maxChars)."\n[... diff truncated ...]";
        }

        return trim($stat."\n\n".$patch);
    }

    public function commit(string $message): void
    {
        $this->write(['git', 'commit', '-m', $message]);
    }

    public function latestVersion(): ?Version
    {
        $tags = preg_split('/\R/', $this->read(['git', 'tag', '--list', 'v*']), -1, PREG_SPLIT_NO_EMPTY);

        return Version::highest($tags ?: []);
    }

    public function tagExists(string $tag): bool
    {
        $tags = preg_split('/\R/', $this->read(['git', 'tag', '--list', $tag]), -1, PREG_SPLIT_NO_EMPTY);

        return in_array($tag, $tags ?: [], true);
    }

    public function createTag(string $tag, string $message): void
    {
        $this->write(['git', 'tag', '-a', $tag, '-m', $message]);
    }

    public function push(string $ref, string $remote = 'origin'): void
    {
        $this->write(['git', 'push', $remote, $ref]);
    }

    /**
     * Run a read-only command and return its trimmed output. Always executes,
     * even in dry-run mode, since reads never mutate the repository.
     *
     * @param  array<int, string>  $command
     */
    private function read(array $command): string
    {
        $result = Process::path($this->path)->run($command);

        if ($result->failed()) {
            throw new RuntimeException(
                "Git command failed in [{$this->path}]: ".implode(' ', $command)."\n".$result->errorOutput()
            );
        }

        return trim($result->output());
    }

    /**
     * Run a mutating command. In dry-run mode the command is logged but never
     * executed.
     *
     * @param  array<int, string>  $command
     */
    private function write(array $command): void
    {
        if ($this->logger !== null) {
            ($this->logger)(implode(' ', $command));
        }

        if ($this->dryRun) {
            return;
        }

        $result = Process::path($this->path)->run($command);

        if ($result->failed()) {
            throw new RuntimeException(
                "Git command failed in [{$this->path}]: ".implode(' ', $command)."\n".$result->errorOutput()
            );
        }
    }
}
