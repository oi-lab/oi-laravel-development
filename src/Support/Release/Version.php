<?php

namespace OiLab\OiLaravelDevelopment\Support\Release;

use InvalidArgumentException;

final class Version
{
    public function __construct(
        public readonly int $major,
        public readonly int $minor,
        public readonly int $patch,
        public readonly string $prefix = 'v',
    ) {}

    /**
     * Parse a tag such as "v1.0.21" or "1.0.21" into a Version, or null when
     * the tag is not a plain semantic version.
     */
    public static function parse(string $tag): ?self
    {
        $tag = trim($tag);

        if (! preg_match('/^(v?)(\d+)\.(\d+)\.(\d+)$/', $tag, $matches)) {
            return null;
        }

        return new self(
            (int) $matches[2],
            (int) $matches[3],
            (int) $matches[4],
            $matches[1] === '' ? '' : 'v',
        );
    }

    /**
     * Pick the highest Version from a list of raw tags, ignoring tags that are
     * not plain semantic versions.
     *
     * @param  iterable<string>  $tags
     */
    public static function highest(iterable $tags): ?self
    {
        $highest = null;

        foreach ($tags as $tag) {
            $version = self::parse($tag);

            if ($version === null) {
                continue;
            }

            if ($highest === null || $version->isGreaterThan($highest)) {
                $highest = $version;
            }
        }

        return $highest;
    }

    /**
     * Return a new Version incremented by the given level.
     */
    public function bump(string $level): self
    {
        return match ($level) {
            'major' => new self($this->major + 1, 0, 0, $this->prefix),
            'minor' => new self($this->major, $this->minor + 1, 0, $this->prefix),
            'patch' => new self($this->major, $this->minor, $this->patch + 1, $this->prefix),
            default => throw new InvalidArgumentException("Unknown bump level [{$level}]."),
        };
    }

    public function isGreaterThan(self $other): bool
    {
        return ([$this->major, $this->minor, $this->patch]
            <=> [$other->major, $other->minor, $other->patch]) > 0;
    }

    public function __toString(): string
    {
        return "{$this->prefix}{$this->major}.{$this->minor}.{$this->patch}";
    }
}
