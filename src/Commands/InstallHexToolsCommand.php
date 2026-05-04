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
        {--composer-scripts : Update root composer.json with architecture scripts}';

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
        $this->publishConfig();
        $this->createDirectories();

        if ($this->option('composer-scripts')) {
            $this->installComposerScripts();
        }

        $this->info('hex-tools installed successfully.');

        return self::SUCCESS;
    }

    protected function publishConfig(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'hex-tools-config',
            '--force' => $this->option('force'),
        ]);
    }

    protected function createDirectories(): void
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
                $this->filesystem->ensureDirectory($dir);
                $this->line("Created: {$dir}");
            } else {
                $this->line("Exists:  {$dir}");
            }
        }
    }

    protected function installComposerScripts(): void
    {
        $path = base_path('composer.json');

        if ($this->composerInstaller->install($path)) {
            $this->info('Composer scripts added to composer.json.');
        } else {
            $this->error('Could not update composer.json.');
        }
    }
}
