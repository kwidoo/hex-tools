<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Generators\ModuleDocsGenerator;
use Kwidoo\HexTools\Support\Filesystem;
use Kwidoo\HexTools\Support\Str;

class InitModuleCommand extends Command
{
    protected $signature = 'hex:module:init
        {module : Module name}
        {--force : Overwrite README/docs files}
        {--with-folders : Create default folder skeleton}';

    protected $description = 'Create module folder skeleton and documentation stub.';

    public function __construct(
        protected HexToolsConfig $config,
        protected Filesystem $filesystem,
        protected ModuleDocsGenerator $docsGenerator
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $module = $this->argument('module');

        if ($this->option('with-folders')) {
            $this->createFolders($module);
        }

        $this->createDocs($module);

        $this->info("Module {$module} initialized.");

        return self::SUCCESS;
    }

    protected function createFolders(string $module): void
    {
        $appPath = $this->config->get('paths.app');

        $folders = [
            "Domain/{$module}/Entities",
            "Domain/{$module}/ValueObjects",
            "Domain/{$module}/Enums",
            "Domain/{$module}/Exceptions",
            "Domain/{$module}/Contracts",
            "Application/{$module}/Data",
            "Application/{$module}/UseCases",
            "Application/{$module}/Services",
            "Application/{$module}/Contracts",
            "Infrastructure/{$module}/Persistence",
            "Infrastructure/{$module}/Mappers",
            "Infrastructure/{$module}/Adapters",
        ];

        foreach ($folders as $folder) {
            $path = $appPath . '/' . $folder;
            $this->filesystem->ensureDirectory($path);
            $this->line("Created: {$path}");
        }
    }

    protected function createDocs(string $module): void
    {
        $docsPath = $this->config->get('paths.docs') . '/modules';
        $kebab = Str::kebab($module);
        $docFile = $docsPath . '/' . $kebab . '.md';

        if ($this->filesystem->exists($docFile) && !$this->option('force')) {
            $this->warn("Docs already exist: {$docFile}. Use --force to overwrite.");
            return;
        }

        $this->filesystem->ensureDirectory($docsPath);
        $content = $this->docsGenerator->generate($module);
        $this->filesystem->put($docFile, $content);

        $this->info("Docs created: {$docFile}");
    }
}
