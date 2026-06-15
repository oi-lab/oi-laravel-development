<?php

namespace OiLab\OiLaravelDevelopment\Commands\Skills;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class InstallSkills extends Command
{
    protected $signature = 'oi:skills
        {skills?* : Skill names to install non-interactively (skips the picker)}
        {--global : Install into the Claude Code user profile (~/.claude)}
        {--project : Install into the current project (.claude and .junie)}
        {--all : Select every discovered skill}';

    protected $description = 'Discover and install AI assistant skills from installed oi-lab packages';

    /**
     * @var array<string, array{name: string, package: string, path: string, rules: ?string, section: ?string, label: string}>
     */
    private array $available = [];

    public function handle(): int
    {
        $this->available = $this->discoverSkills();

        if (empty($this->available)) {
            $this->warn('No oi-lab skills found. Are the oi-lab packages installed and declaring "extra.oi-lab.skills"?');

            return self::SUCCESS;
        }

        $selected = $this->resolveSelection();

        if (empty($selected)) {
            $this->info('No skills selected.');

            return self::SUCCESS;
        }

        $scope = $this->resolveScope();

        if ($scope === 'global' && $this->homeDirectory() === null) {
            $this->error('Could not determine the home directory. Use --project instead.');

            return self::FAILURE;
        }

        foreach ($selected as $name) {
            $this->installSkill($this->available[$name], $scope);
        }

        $this->newLine();
        $this->info('✅ Skills installed. Restart Claude Code or run /doctor if they do not appear.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, array{name: string, package: string, path: string, rules: ?string, section: ?string, label: string}>
     */
    private function discoverSkills(): array
    {
        $skills = [];

        foreach ($this->packagePaths() as $package => $installPath) {
            $composerFile = $installPath.'/composer.json';

            if (! File::exists($composerFile)) {
                continue;
            }

            $data = json_decode(File::get($composerFile), true);
            $packageName = $data['name'] ?? $package;
            $declared = Arr::get($data, 'extra.oi-lab.skills', []);

            foreach ($declared as $skill) {
                if (empty($skill['name']) || empty($skill['path'])) {
                    continue;
                }

                $name = $skill['name'];

                $skills[$name] = [
                    'name' => $name,
                    'package' => $packageName,
                    'path' => $installPath.'/'.ltrim($skill['path'], '/'),
                    'rules' => isset($skill['rules']) ? $installPath.'/'.ltrim($skill['rules'], '/') : null,
                    'section' => $skill['section'] ?? null,
                    'label' => $skill['description'] ?? $packageName,
                ];
            }
        }

        ksort($skills);

        return $skills;
    }

    /**
     * Map of package identifier => install path to scan for skill declarations.
     *
     * @return array<string, string>
     */
    private function packagePaths(): array
    {
        $paths = [];

        foreach (InstalledVersions::getInstalledPackages() as $package) {
            if (! str_starts_with($package, 'oi-lab/')) {
                continue;
            }

            $installPath = InstalledVersions::getInstallPath($package);

            if ($installPath !== null) {
                $paths[$package] = rtrim($installPath, '/');
            }
        }

        foreach ((array) config('oi-laravel-development.skill_paths', []) as $extraPath) {
            $extraPath = rtrim($extraPath, '/');
            $paths[$extraPath] = $extraPath;
        }

        return $paths;
    }

    /**
     * @return array<int, string>
     */
    private function resolveSelection(): array
    {
        if ($this->option('all')) {
            return array_keys($this->available);
        }

        $requested = $this->argument('skills');

        if (! empty($requested)) {
            $unknown = array_diff($requested, array_keys($this->available));

            if (! empty($unknown)) {
                $this->error('Unknown skill(s): '.implode(', ', $unknown));
                $this->line('Available: '.implode(', ', array_keys($this->available)));

                return [];
            }

            return array_values($requested);
        }

        return multiselect(
            label: 'Which skills should be installed?',
            options: array_map(
                fn (array $skill): string => "{$skill['name']}  —  {$skill['label']}",
                $this->available
            ),
            default: array_keys($this->available),
            hint: 'Space to toggle, Enter to confirm.',
        );
    }

    private function resolveScope(): string
    {
        if ($this->option('global')) {
            return 'global';
        }

        if ($this->option('project')) {
            return 'project';
        }

        return select(
            label: 'Where should the skills be installed?',
            options: [
                'project' => 'This project (.claude/skills and .junie/skills)',
                'global' => 'Claude Code user profile (~/.claude/skills — available in all projects)',
            ],
            default: 'project',
        );
    }

    /**
     * @param  array{name: string, package: string, path: string, rules: ?string, section: ?string, label: string}  $skill
     */
    private function installSkill(array $skill, string $scope): void
    {
        if (! File::exists($skill['path'])) {
            $this->warn("Skipping {$skill['name']}: source not found at {$skill['path']}.");

            return;
        }

        foreach ($this->skillTargets($scope) as $base) {
            $this->copySkill($skill, $base.'/'.$skill['name']);
        }

        if ($skill['rules'] !== null && $skill['section'] !== null && File::exists($skill['rules'])) {
            $this->addRulesToClaudeMd($skill, $this->claudeMdPath($scope));
        }
    }

    /**
     * @return array<int, string>
     */
    private function skillTargets(string $scope): array
    {
        if ($scope === 'global') {
            return [$this->homeDirectory().'/.claude/skills'];
        }

        return [
            base_path('.claude/skills'),
            base_path('.junie/skills'),
        ];
    }

    private function claudeMdPath(string $scope): string
    {
        return $scope === 'global'
            ? $this->homeDirectory().'/.claude/CLAUDE.md'
            : base_path('CLAUDE.md');
    }

    /**
     * @param  array{name: string, package: string, path: string, rules: ?string, section: ?string, label: string}  $skill
     */
    private function copySkill(array $skill, string $target): void
    {
        File::ensureDirectoryExists($target);

        if (File::isDirectory($skill['path'])) {
            File::copyDirectory($skill['path'], $target);
        } else {
            File::copy($skill['path'], $target.'/SKILL.md');
        }

        $this->info("Installed {$skill['name']}: {$target}/");
    }

    /**
     * @param  array{name: string, package: string, path: string, rules: ?string, section: ?string, label: string}  $skill
     */
    private function addRulesToClaudeMd(array $skill, string $claudeMdPath): void
    {
        $sectionHeader = '=== '.$skill['section'].' ===';
        $body = File::get($skill['rules']);
        $newSection = $sectionHeader."\n\n".trim($body)."\n";

        if (! File::exists($claudeMdPath)) {
            File::ensureDirectoryExists(dirname($claudeMdPath));
            File::put($claudeMdPath, $newSection."\n");
            $this->info('Created '.basename($claudeMdPath)." with {$skill['package']} rules.");

            return;
        }

        $content = File::get($claudeMdPath);

        if (! str_contains($content, $sectionHeader)) {
            $separator = str_ends_with($content, "\n") ? "\n" : "\n\n";
            File::put($claudeMdPath, $content.$separator.$newSection."\n");
            $this->info("Added {$skill['package']} rules section to ".basename($claudeMdPath).'.');

            return;
        }

        $escaped = preg_quote($sectionHeader, '#');
        $updated = preg_replace(
            '#'.$escaped.'.*?(?=\n===|\z)#s',
            $newSection,
            $content
        );

        File::put($claudeMdPath, $updated);
        $this->info("Updated {$skill['package']} rules section in ".basename($claudeMdPath).'.');
    }

    private function homeDirectory(): ?string
    {
        $home = getenv('HOME') ?: ($_SERVER['HOME'] ?? null);

        if ($home === null && isset($_SERVER['HOMEDRIVE'], $_SERVER['HOMEPATH'])) {
            $home = $_SERVER['HOMEDRIVE'].$_SERVER['HOMEPATH'];
        }

        return $home ? rtrim($home, '/\\') : null;
    }
}
