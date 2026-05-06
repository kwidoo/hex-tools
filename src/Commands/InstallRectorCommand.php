<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Generators\RectorComposerScriptsInstaller;
use Kwidoo\HexTools\Generators\RectorConfigGenerator;
use Kwidoo\HexTools\Support\Filesystem;
use Kwidoo\HexTools\Support\ToolAvailability;

class InstallRectorCommand extends Command
{
    protected $signature = 'hex:rector:install
        {--force : Overwrite existing rector.php}
        {--composer-scripts : Update root composer.json with Rector scripts}
        {--dry-run : Preview changes without writing files}
        {--no-overwrite : Skip existing files without prompting}';

    protected $description = 'Install Rector config and optional composer scripts.';

    public function __construct(
        protected RectorConfigGenerator $generator,
        protected RectorComposerScriptsInstaller $composerInstaller,
        protected Filesystem $filesystem,
        protected ToolAvailability $toolAvailability
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!$this->toolAvailability->hasRector()) {
            $this->warn('Rector is not installed.');
            $this->line('');
            $this->line('Install it with:');
            $this->line('');
            $this->line('  composer require --dev rector/rector');
            $this->line('');
        }

        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');
        $noOverwrite = (bool) $this->option('no-overwrite');

        if ($dryRun) {
            $this->info('[DRY RUN] No files will be written.');
            $this->line('');
        }

        $this->generateFile(base_path('rector.php'), $this->generator->generate(), $force, $dryRun, $noOverwrite);
        $this->generateDocs($force, $dryRun, $noOverwrite);

        if ($this->option('composer-scripts')) {
            $this->installComposerScripts($dryRun);
        }

        if ($dryRun) {
            $this->line('');
            $this->info('[DRY RUN] Completed. No changes were made.');
            return self::SUCCESS;
        }

        $this->info('Rector config installed successfully.');

        return self::SUCCESS;
    }

    protected function generateFile(string $path, string $content, bool $force, bool $dryRun = false, bool $noOverwrite = false): void
    {
        $relative = basename($path);

        if (file_exists($path) && $noOverwrite) {
            $this->line("Skipped (exists): {$relative}");
            return;
        }

        if (file_exists($path) && !$force) {
            if ($dryRun) {
                $this->warn("[DRY RUN] Would overwrite: {$relative}");
            } else {
                $this->line("Skipped (exists): {$relative}");
            }
            return;
        }

        if ($dryRun) {
            $this->line(file_exists($path) ? "[DRY RUN] Would overwrite: {$relative}" : "[DRY RUN] Would generate: {$relative}");
        } else {
            file_put_contents($path, $content);
            $this->line("Generated: {$relative}");
        }
    }

    protected function generateDocs(bool $force, bool $dryRun = false, bool $noOverwrite = false): void
    {
        $docsPath = base_path('docs/architecture/rector.md');
        $this->filesystem->ensureDirectory(dirname($docsPath));
        $this->generateFile($docsPath, $this->generator->generateDocs(), $force, $dryRun, $noOverwrite);
    }

    protected function installComposerScripts(bool $dryRun): void
    {
        if ($dryRun) {
            $this->line("[DRY RUN] Would update composer.json with Rector scripts.");
        } else {
            if ($this->composerInstaller->install(base_path('composer.json'))) {
                $this->info('Rector composer scripts added to composer.json.');
            } else {
                $this->error('Could not update composer.json.');
            }
        }
    }
}
