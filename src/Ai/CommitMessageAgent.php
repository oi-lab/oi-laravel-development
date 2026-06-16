<?php

namespace OiLab\OiLaravelDevelopment\Ai;

use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

#[Provider('ollama')]
class CommitMessageAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        $override = config('oi-laravel-development.release.ai.instructions');

        if (is_string($override) && trim($override) !== '') {
            return $override;
        }

        $maxBullets = (int) config('oi-laravel-development.release.ai.max_body_bullets', 8);

        return <<<PROMPT
            You are a senior software engineer writing a thorough Git commit
            message that summarises a staged diff.

            Rules:
            - Reply with the commit message only. No preamble, no explanation, no
              Markdown code fences, no surrounding quotes.
            - First line: a Conventional Commits header "type(scope): subject"
              (types: feat, fix, refactor, perf, docs, test, chore, build, ci),
              written in the imperative mood and at most 72 characters.
            - Then a blank line, then a body that explains the change in depth:
                - Use "- " bullet points, one per notable change.
                - Describe what changed and, importantly, why it changed.
                - Group related changes and name the affected files, classes or
                  components when it helps the reader.
            - Aim for up to {$maxBullets} bullet points for a non-trivial change,
              fewer for a small one. Be thorough but relevant: never restate the
              raw diff line by line and never invent changes absent from the diff.
            PROMPT;
    }
}
