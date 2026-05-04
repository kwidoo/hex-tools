<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Generators\PhpStanComposerScriptsInstaller;
use Kwidoo\HexTools\Generators\PhpStanConfigGenerator;
use Kwidoo\HexTools\Generators\StaticAnalysisDocsGenerator;
use Kwidoo\HexTools\Support\Filesystem;
use Kwidoo\HexTools\Support\ToolAvailability;

class InstallPhpStanCommand extends Command
{
    protected $signature = 'hex:phpstan:install
        {--force : Overwrite existing PHPStan config files}
        {--composer-scripts : Update root composer.json with PHPStan scripts}
        {--with-baseline : Generate baseline command/script references}';

    protected $description = 'Install PHPStan/Larastan config files and optional composer scripts.';

    public function __construct(
        protected PhpStanConfigGenerator $generator,
        protected StaticAnalysisDocsGenerator $docsGenerator,
        protected PhpStanComposerScriptsInstaller $composerInstaller,
        protected Filesystem $filesystem,
        protected ToolAvailability $toolAvailability
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!$this->toolAvailability->hasPhpStan()) {
            $this->warn('PHPStan/Larastan is not installed.');
            $this->line('');
            $this->line('Install it with:');
            $this->line('');
            $this->line('  composer require --dev larastan/larastan');
            $this->line('');
        }

        $force = (bool) $this->option('force');
        $withBaseline = (bool) $this->option('with-baseline');

        $this->generateFile(
            base_path('phpstan.neon.dist'),
            $this->generator->generateMain($withBaseline),
            $force
        );

        $this->generateFile(
            base_path('phpstan-domain.neon'),
            $this->generator->generateDomain(),
            $force
        );

        $this->generateFile(
            base_path('phpstan-application.neon'),
            $this->generator->generateApplication(),
            $force
        );

        $this->generateDocs($force);

        if ($this->option('composer-scripts')) {
            $this->installComposerScripts();
        } else {
            $this->printComposerScriptsSnippet();
        }

        $this->info('PHPStan config installed successfully.');

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
        $docsPath = base_path('docs/architecture/static-analysis.md');
        $this->filesystem->ensureDirectory(dirname($docsPath));

        $content = $this->docsGenerator->generate();
        $this->generateFile($docsPath, $content, $force);
    }

    protected function installComposerScripts(): void
    {
        $path = base_path('composer.json');

        if ($this->composerInstaller->install($path)) {
            $this->info('PHPStan composer scripts added to composer.json.');
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
