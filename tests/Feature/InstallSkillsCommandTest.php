<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->base = sys_get_temp_dir().'/oi-skills-base-'.uniqid();
    $this->pkg = sys_get_temp_dir().'/oi-skills-pkg-'.uniqid();

    File::ensureDirectoryExists($this->base);
    File::ensureDirectoryExists($this->pkg.'/resources/skills/oilab-demo');
    File::ensureDirectoryExists($this->pkg.'/resources/stubs');

    File::put($this->pkg.'/composer.json', json_encode([
        'name' => 'oi-lab/oi-laravel-demo',
        'extra' => [
            'oi-lab' => [
                'skills' => [[
                    'name' => 'oilab-demo',
                    'description' => 'Demo skill',
                    'path' => 'resources/skills/oilab-demo',
                    'rules' => 'resources/stubs/claude-rules.md',
                    'section' => 'oi-lab/oi-laravel-demo rules',
                ]],
            ],
        ],
    ]));

    File::put($this->pkg.'/resources/skills/oilab-demo/SKILL.md', "# Demo Skill\n");
    File::put($this->pkg.'/resources/stubs/claude-rules.md', "Activate `oilab-demo` when working on demos.\n");

    app()->setBasePath($this->base);
    config()->set('oi-laravel-development.skill_paths', [$this->pkg]);
});

afterEach(function () {
    foreach ([$this->base, $this->pkg] as $dir) {
        if (is_dir($dir)) {
            File::deleteDirectory($dir);
        }
    }
});

it('discovers and installs a declared skill into the project', function () {
    $this->artisan('oi:skills', ['skills' => ['oilab-demo'], '--project' => true])
        ->assertSuccessful();

    expect(File::exists($this->base.'/.claude/skills/oilab-demo/SKILL.md'))->toBeTrue()
        ->and(File::exists($this->base.'/.junie/skills/oilab-demo/SKILL.md'))->toBeTrue();
});

it('adds the rules section to CLAUDE.md', function () {
    $this->artisan('oi:skills', ['skills' => ['oilab-demo'], '--project' => true])
        ->assertSuccessful();

    $claude = File::get($this->base.'/CLAUDE.md');

    expect($claude)->toContain('=== oi-lab/oi-laravel-demo rules ===')
        ->and($claude)->toContain('Activate `oilab-demo`');
});

it('does not duplicate the rules section when run twice', function () {
    $this->artisan('oi:skills', ['skills' => ['oilab-demo'], '--project' => true])->assertSuccessful();
    $this->artisan('oi:skills', ['skills' => ['oilab-demo'], '--project' => true])->assertSuccessful();

    $claude = File::get($this->base.'/CLAUDE.md');

    expect(substr_count($claude, '=== oi-lab/oi-laravel-demo rules ==='))->toBe(1);
});

it('installs every skill with the --all flag', function () {
    $this->artisan('oi:skills', ['--all' => true, '--project' => true])
        ->assertSuccessful();

    expect(File::exists($this->base.'/.claude/skills/oilab-demo/SKILL.md'))->toBeTrue();
});

it('fails for an unknown skill name', function () {
    $this->artisan('oi:skills', ['skills' => ['does-not-exist'], '--project' => true])
        ->expectsOutputToContain('Unknown skill(s): does-not-exist')
        ->assertSuccessful();

    expect(File::exists($this->base.'/.claude/skills'))->toBeFalse();
});
