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
        {--composer-scripts : Update root composer.json with Rector scripts}';

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

        $this->generateFile(base_path('rector.php'), $this->generator->generate(), $force);
        $this->generateDocs($force);

        if ($this->option('composer-scripts')) {
            $this->installComposerScripts();
        }

        $this->info('Rector config installed successfully.');

        return self::SUCCESS;
    }

    protected function generateFile(string $path, string $content, bool $force): void
    {
        $relative = basename($path);

        if (file_exists($path) && !$force) {
            $this->line("Skipped (exists): {$relative}");
            return;
        }

        file_put_contents($path, $content);
        $this->line("Generated: {$relative}");
    }

    protected function generateDocs(bool $force): void
    {
        $docsPath = base_path('docs/architecture/rector.md');
        $this->filesystem->ensureDirectory(dirname($docsPath));
        $this->generateFile($docsPath, $this->generator->generateDocs(), $force);
    }

    protected function installComposerScripts(): void
    {
        if ($this->composerInstaller->install(base_path('composer.json'))) {
            $this->info('Rector composer scripts added to composer.json.');
        } else {
            $this->error('Could not update composer.json.');
        }
    }
}
