<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Generators\ComposerScriptsInstaller;
use Kwidoo\HexTools\Support\Filesystem;

class InstallHexToolsCommand extends Command
{
    protected $signature = 'hex:install
        {--force : Overwrite existing files}
        {--composer-scripts : Update root composer.json with architecture scripts}
        {--dry-run : Preview changes without writing files}
        {--no-overwrite : Skip existing files without prompting}';

    protected $description = 'Install hex-tools: publish config and create architecture folders.';

    public function __construct(
        protected HexToolsConfig $config,
        protected Filesystem $filesystem,
        protected ComposerScriptsInstaller $composerInstaller
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $noOverwrite = (bool) $this->option('no-overwrite');

        if ($dryRun) {
            $this->info('[DRY RUN] No files will be written.');
            $this->line('');
        }

        $this->publishConfig($dryRun, $noOverwrite);
        $this->createDirectories($dryRun);

        if ($this->option('composer-scripts')) {
            $this->installComposerScripts($dryRun);
        }

        if ($dryRun) {
            $this->line('');
            $this->info('[DRY RUN] Completed. No changes were made.');
            return self::SUCCESS;
        }

        $this->info('hex-tools installed successfully.');

        return self::SUCCESS;
    }

    protected function publishConfig(bool $dryRun, bool $noOverwrite): void
    {
        $configPath = base_path('config/hex-tools.php');
        $exists = file_exists($configPath);

        if ($exists && $noOverwrite) {
            $this->line("Skipped (exists): config/hex-tools.php");
            return;
        }

        if ($exists && !$this->option('force')) {
            if ($dryRun) {
                $this->warn("[DRY RUN] Would overwrite: config/hex-tools.php");
            } else {
                $this->call('vendor:publish', [
                    '--tag' => 'hex-tools-config',
                    '--force' => false,
                ]);
            }
            return;
        }

        if ($dryRun) {
            $this->line("[DRY RUN] Would publish: config/hex-tools.php");
        } else {
            $this->call('vendor:publish', [
                '--tag' => 'hex-tools-config',
                '--force' => $this->option('force'),
            ]);
        }
    }

    protected function createDirectories(bool $dryRun): void
    {
        $paths = $this->config->paths();
        $docs = $paths['docs'] ?? base_path('docs/architecture');

        $directories = [
            $docs,
            $docs . '/modules',
            $paths['adr'] ?? base_path('docs/adr'),
            $paths['build'] ?? base_path('build/architecture'),
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                if ($dryRun) {
                    $this->line("[DRY RUN] Would create: {$dir}");
                } else {
                    $this->filesystem->ensureDirectory($dir);
                    $this->line("Created: {$dir}");
                }
            } else {
                $this->line("Exists:  {$dir}");
            }
        }
    }

    protected function installComposerScripts(bool $dryRun): void
    {
        $path = base_path('composer.json');

        if ($dryRun) {
            $this->line("[DRY RUN] Would update composer.json with architecture scripts.");
        } else {
            if ($this->composerInstaller->install($path)) {
                $this->info('Composer scripts added to composer.json.');
            } else {
                $this->error('Could not update composer.json.');
            }
        }
    }
}
