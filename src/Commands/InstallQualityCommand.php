<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Generators\QualityComposerScriptsInstaller;
use Kwidoo\HexTools\Support\Filesystem;

class InstallQualityCommand extends Command
{
    protected $signature = 'hex:quality:install
        {--profile=basic : basic|strict|ci}
        {--force : Overwrite existing generated config files}
        {--composer-scripts : Update root composer.json with quality scripts}';

    protected $description = 'Install a quality profile (basic, strict, or ci).';

    public function __construct(
        protected QualityComposerScriptsInstaller $composerInstaller,
        protected Filesystem $filesystem
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $profile = (string) $this->option('profile');
        $force = (bool) $this->option('force');
        $composerScripts = (bool) $this->option('composer-scripts');

        $this->info("Installing quality profile: {$profile}");

        $this->installBasic($force, $composerScripts);

        if (in_array($profile, ['strict', 'ci'])) {
            $this->installStrict($force, $composerScripts);
        }

        if ($profile === 'ci') {
            $this->installCi($force);
        }

        if ($composerScripts) {
            $this->installComposerScripts($profile);
        }

        $this->info("Quality profile '{$profile}' installed successfully.");

        return self::SUCCESS;
    }

    protected function installBasic(bool $force, bool $composerScripts): void
    {
        $this->call('hex:deptrac:layers', $force ? ['--force' => true] : []);
        $this->call('hex:deptrac:modules', $force ? ['--force' => true] : []);
        $this->call('hex:phpstan:install', array_filter([
            '--force' => $force ?: null,
        ]));
        $this->call('hex:pint:install', array_filter([
            '--force' => $force ?: null,
        ]));
        $this->call('hex:docs:generate', array_filter([
            '--force' => $force ?: null,
        ]));
    }

    protected function installStrict(bool $force, bool $composerScripts): void
    {
        $this->call('hex:phpmd:install', array_filter([
            '--force' => $force ?: null,
        ]));
        $this->call('hex:rector:install', array_filter([
            '--force' => $force ?: null,
        ]));
    }

    protected function installCi(bool $force): void
    {
        $buildPath = base_path('build/architecture');
        $this->filesystem->ensureDirectory($buildPath);
        $this->line("Ensured: build/architecture");

        $ciDocsPath = base_path('docs/architecture/ci.md');
        $this->filesystem->ensureDirectory(dirname($ciDocsPath));

        if (!file_exists($ciDocsPath) || $force) {
            $content = file_get_contents(dirname(__DIR__, 2) . '/stubs/docs/ci.md.stub');
            file_put_contents($ciDocsPath, $content);
            $this->line("Generated: docs/architecture/ci.md");
        } else {
            $this->line("Skipped (exists): docs/architecture/ci.md");
        }
    }

    protected function installComposerScripts(string $profile): void
    {
        $this->composerInstaller->setProfile($profile);
        $path = base_path('composer.json');

        if ($this->composerInstaller->install($path)) {
            $this->info("Quality composer scripts ({$profile}) added to composer.json.");
        } else {
            $this->error('Could not update composer.json.');
        }
    }
}
