<?php

use OiLab\OiLaravelDevelopment\Support\Release\Version;

it('parses a prefixed semantic version', function () {
    $version = Version::parse('v1.0.21');

    expect((string) $version)->toBe('v1.0.21')
        ->and($version->major)->toBe(1)
        ->and($version->minor)->toBe(0)
        ->and($version->patch)->toBe(21);
});

it('returns null for non-semantic tags', function (string $tag) {
    expect(Version::parse($tag))->toBeNull();
})->with(['latest', 'v1.0', '1.2.3.4', 'release-1']);

it('bumps each level correctly', function (string $level, string $expected) {
    expect((string) Version::parse('v1.4.9')->bump($level))->toBe($expected);
})->with([
    'patch' => ['patch', 'v1.4.10'],
    'minor' => ['minor', 'v1.5.0'],
    'major' => ['major', 'v2.0.0'],
]);

it('selects the highest version regardless of lexical order', function () {
    $highest = Version::highest(['v1.0.2', 'v1.0.21', 'v1.0.9', 'not-a-tag', 'v1.0.3']);

    expect((string) $highest)->toBe('v1.0.21');
});

it('returns null when no semantic tag exists', function () {
    expect(Version::highest(['latest', 'stable']))->toBeNull();
});
