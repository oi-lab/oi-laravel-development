<?php

namespace OiLab\OiLaravelDevelopment\Services\Release;

use OiLab\OiLaravelDevelopment\Ai\CommitMessageAgent;
use Throwable;

class CommitMessageGenerator
{
    private ?string $error = null;

    public function __construct(private readonly CommitMessageAgent $agent) {}

    /**
     * Generate a commit message from a staged diff summary, or null when the
     * AI provider is unreachable or returns nothing usable. Inspect lastError()
     * to find out why generation failed.
     */
    public function generate(string $diffSummary): ?string
    {
        $this->error = null;

        if (trim($diffSummary) === '') {
            $this->error = 'No staged changes to summarise.';

            return null;
        }

        try {
            $response = $this->agent->prompt(
                $diffSummary,
                model: config('oi-laravel-development.release.ai.model'),
                timeout: (int) config('oi-laravel-development.release.ai.timeout', 120),
            );
        } catch (Throwable $e) {
            $this->error = $e->getMessage();

            return null;
        }

        $message = $this->clean((string) $response->text);

        if ($message === '') {
            $this->error = 'The model returned an empty message.';

            return null;
        }

        return $message;
    }

    /**
     * The reason the last generate() call returned null, if any.
     */
    public function lastError(): ?string
    {
        return $this->error;
    }

    /**
     * Strip reasoning blocks, code fences and stray quoting that local models
     * sometimes wrap around the message.
     */
    private function clean(string $message): string
    {
        $message = preg_replace('/<think>.*?<\/think>/is', '', $message) ?? $message;
        $message = preg_replace('/^```[a-z]*\n?|\n?```$/i', '', trim($message)) ?? $message;
        $message = str_replace(['\r\n', '\n', '\r'], "\n", trim($message));
        $message = preg_replace('/[ \t]+$/m', '', $message) ?? $message;
        $message = trim($message);

        if (preg_match('/^"(.*)"$/s', $message, $matches) === 1) {
            $message = $matches[1];
        }

        return trim($message);
    }
}
