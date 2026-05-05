<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Generators\PintComposerScriptsInstaller;
use Kwidoo\HexTools\Generators\PintConfigGenerator;
use Kwidoo\HexTools\Support\Filesystem;
use Kwidoo\HexTools\Support\StubRenderer;
use Kwidoo\HexTools\Support\ToolAvailability;

class InstallPintCommand extends Command
{
    protected $signature = 'hex:pint:install
        {--force : Overwrite existing pint.json}
        {--composer-scripts : Update root composer.json with Pint scripts}';

    protected $description = 'Install Pint config and optional composer scripts.';

    public function __construct(
        protected PintConfigGenerator $generator,
        protected PintComposerScriptsInstaller $composerInstaller,
        protected Filesystem $filesystem,
        protected StubRenderer $renderer,
        protected ToolAvailability $toolAvailability
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!$this->toolAvailability->hasPint()) {
            $this->warn('Pint is not installed.');
            $this->line('');
            $this->line('Install it with:');
            $this->line('');
            $this->line('  composer require --dev laravel/pint');
            $this->line('');
        }

        $force = (bool) $this->option('force');

        $this->generateFile(base_path('pint.json'), $this->generator->generate(), $force);
        $this->generateDocs($force);

        if ($this->option('composer-scripts')) {
            $this->installComposerScripts();
        }

        $this->info('Pint config installed successfully.');

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
        $docsPath = base_path('docs/architecture/pint.md');
        $this->filesystem->ensureDirectory(dirname($docsPath));

        $preset = config('hex-tools.pint.preset', 'laravel');
        $content = $this->renderer->render(
            dirname(__DIR__, 2) . '/stubs/docs/pint.md.stub',
            ['preset' => $preset]
        );

        $this->generateFile($docsPath, $content, $force);
    }

    protected function installComposerScripts(): void
    {
        if ($this->composerInstaller->install(base_path('composer.json'))) {
            $this->info('Pint composer scripts added to composer.json.');
        } else {
            $this->error('Could not update composer.json.');
        }
    }
}
