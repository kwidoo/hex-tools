<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Generators\PhpMdComposerScriptsInstaller;
use Kwidoo\HexTools\Generators\PhpMdDocsGenerator;
use Kwidoo\HexTools\Generators\PhpMdRulesetGenerator;
use Kwidoo\HexTools\Support\Filesystem;
use Kwidoo\HexTools\Support\ToolAvailability;

class InstallPhpMdCommand extends Command
{
    protected $signature = 'hex:phpmd:install
        {--force : Overwrite existing PHPMD config files}
        {--composer-scripts : Update root composer.json with PHPMD scripts}
        {--with-baseline : Include baseline command/script references}
        {--per-layer : Generate separate rulesets for main, domain, and application layers}';

    protected $description = 'Install PHPMD config and optional composer scripts.';

    public function __construct(
        protected PhpMdRulesetGenerator $generator,
        protected PhpMdDocsGenerator $docsGenerator,
        protected PhpMdComposerScriptsInstaller $composerInstaller,
        protected Filesystem $filesystem,
        protected ToolAvailability $toolAvailability
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!$this->toolAvailability->hasPhpMd()) {
            $this->warn('PHPMD is not installed.');
            $this->line('');
            $this->line('Install it with:');
            $this->line('');
            $this->line('  composer require --dev phpmd/phpmd');
            $this->line('');
        }

        $force = (bool) $this->option('force');
        $perLayer = (bool) $this->option('per-layer');

        if ($perLayer) {
            $this->generatePerLayerRulesets($force);
        } else {
            $this->generateFile(base_path('phpmd.xml'), $this->generator->generate(), $force);
        }

        $this->generateDocs($force);

        if ($this->option('composer-scripts')) {
            $this->installComposerScripts();
        } else {
            $this->printComposerScriptsSnippet();
        }

        $this->info('PHPMD config installed successfully.');

        return self::SUCCESS;
    }

    protected function generatePerLayerRulesets(bool $force): void
    {
        $this->generateFile(base_path('phpmd.xml'), $this->generator->generateForLayer('main'), $force);
        $this->generateFile(base_path('phpmd-domain.xml'), $this->generator->generateForLayer('domain'), $force);
        $this->generateFile(base_path('phpmd-application.xml'), $this->generator->generateForLayer('application'), $force);
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
        $docsPath = base_path('docs/architecture/phpmd.md');
        $this->filesystem->ensureDirectory(dirname($docsPath));
        $this->generateFile($docsPath, $this->docsGenerator->generate(), $force);
    }

    protected function installComposerScripts(): void
    {
        $path = base_path('composer.json');

        if ($this->composerInstaller->install($path)) {
            $this->info('PHPMD composer scripts added to composer.json.');
        } else {
            $this->error('Could not update composer.json.');
        }
    }

    protected function printComposerScriptsSnippet(): void
    {
        $this->line('');
        $this->line('Add the following scripts to your composer.json:');
        $this->line('');
        $this->line('  "scripts": {');
        foreach ($this->composerInstaller->getScripts() as $key => $command) {
            $this->line("    \"{$key}\": \"{$command}\",");
        }
        $this->line('  }');
        $this->line('');
        $this->line('Or re-run with --composer-scripts to add them automatically.');
    }
}
