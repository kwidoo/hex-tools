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
        {--composer-scripts : Update root composer.json with quality scripts}
        {--dry-run : Preview changes without writing files}
        {--no-overwrite : Skip existing files without prompting}';

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
        $dryRun = (bool) $this->option('dry-run');
        $noOverwrite = (bool) $this->option('no-overwrite');

        if ($dryRun) {
            $this->info('[DRY RUN] No files will be written.');
            $this->line('');
        }

        $this->info("Installing quality profile: {$profile}");

        $this->installBasic($force, $composerScripts, $dryRun, $noOverwrite);

        if (in_array($profile, ['strict', 'ci'])) {
            $this->installStrict($force, $composerScripts, $dryRun, $noOverwrite);
        }

        if ($profile === 'ci') {
            $this->installCi($force, $dryRun, $noOverwrite);
        }

        if ($composerScripts && !$dryRun) {
            $this->installComposerScripts($profile);
        } elseif ($composerScripts && $dryRun) {
            $this->line("[DRY RUN] Would update composer.json with quality scripts ({$profile}).");
        }

        if ($dryRun) {
            $this->line('');
            $this->info('[DRY RUN] Completed. No changes were made.');
            return self::SUCCESS;
        }

        $this->info("Quality profile '{$profile}' installed successfully.");

        return self::SUCCESS;
    }

    protected function installBasic(bool $force, bool $composerScripts, bool $dryRun, bool $noOverwrite): void
    {
        $this->call('hex:deptrac:layers', array_filter([
            '--force' => $force ?: null,
            '--dry-run' => $dryRun ?: null,
            '--no-overwrite' => $noOverwrite ?: null,
        ]));
        $this->call('hex:deptrac:modules', array_filter([
            '--force' => $force ?: null,
            '--dry-run' => $dryRun ?: null,
            '--no-overwrite' => $noOverwrite ?: null,
        ]));
        $this->call('hex:phpstan:install', array_filter([
            '--force' => $force ?: null,
            '--dry-run' => $dryRun ?: null,
            '--no-overwrite' => $noOverwrite ?: null,
        ]));
        $this->call('hex:pint:install', array_filter([
            '--force' => $force ?: null,
            '--dry-run' => $dryRun ?: null,
            '--no-overwrite' => $noOverwrite ?: null,
        ]));
        $this->call('hex:docs:generate', array_filter([
            '--force' => $force ?: null,
            '--dry-run' => $dryRun ?: null,
            '--no-overwrite' => $noOverwrite ?: null,
        ]));
    }

    protected function installStrict(bool $force, bool $composerScripts, bool $dryRun, bool $noOverwrite): void
    {
        $this->call('hex:phpmd:install', array_filter([
            '--force' => $force ?: null,
            '--dry-run' => $dryRun ?: null,
            '--no-overwrite' => $noOverwrite ?: null,
        ]));
        $this->call('hex:rector:install', array_filter([
            '--force' => $force ?: null,
            '--dry-run' => $dryRun ?: null,
            '--no-overwrite' => $noOverwrite ?: null,
        ]));
    }

    protected function installCi(bool $force, bool $dryRun, bool $noOverwrite): void
    {
        $buildPath = base_path('build/architecture');
        $this->filesystem->ensureDirectory($buildPath);
        
        if (!is_dir($buildPath)) {
            if ($dryRun) {
                $this->line("[DRY RUN] Would create: build/architecture");
            } else {
                $this->line("Ensured: build/architecture");
            }
        } else {
            $this->line("Exists:  build/architecture");
        }

        $ciDocsPath = base_path('docs/architecture/ci.md');
        $this->filesystem->ensureDirectory(dirname($ciDocsPath));

        if (!file_exists($ciDocsPath) || $force) {
            if ($noOverwrite && file_exists($ciDocsPath)) {
                $this->line("Skipped (exists): docs/architecture/ci.md");
                return;
            }
            if ($dryRun) {
                $this->line(file_exists($ciDocsPath) ? "[DRY RUN] Would overwrite: docs/architecture/ci.md" : "[DRY RUN] Would generate: docs/architecture/ci.md");
            } else {
                $content = file_get_contents(dirname(__DIR__, 2) . '/stubs/docs/ci.md.stub');
                file_put_contents($ciDocsPath, $content);
                $this->line("Generated: docs/architecture/ci.md");
            }
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
