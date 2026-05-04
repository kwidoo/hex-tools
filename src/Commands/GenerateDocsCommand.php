<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Generators\ModuleDocsGenerator;
use Kwidoo\HexTools\Support\Filesystem;
use Kwidoo\HexTools\Support\StubRenderer;
use Kwidoo\HexTools\Support\Str;

class GenerateDocsCommand extends Command
{
    protected $signature = 'hex:docs:generate
        {--force : Overwrite existing docs}
        {--module= : Generate docs only for one module}';

    protected $description = 'Generate architecture documentation from config.';

    public function __construct(
        protected HexToolsConfig $config,
        protected Filesystem $filesystem,
        protected ModuleDocsGenerator $docsGenerator,
        protected StubRenderer $renderer
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $docsPath = $this->config->get('paths.docs');
        $module = $this->option('module');

        $this->filesystem->ensureDirectory($docsPath);
        $this->filesystem->ensureDirectory($docsPath . '/modules');

        if ($module) {
            $this->generateModuleDoc($module, $docsPath);
        } else {
            $this->generateDeptracDoc($docsPath);
            $this->generateModulesOverview($docsPath);

            foreach ($this->config->modules() as $mod) {
                $this->generateModuleDoc($mod, $docsPath);
            }
        }

        $this->info('Architecture docs generated.');

        return self::SUCCESS;
    }

    protected function generateDeptracDoc(string $docsPath): void
    {
        $file = $docsPath . '/deptrac.md';

        if ($this->filesystem->exists($file) && !$this->option('force')) {
            $this->warn("Exists (skipping): {$file}");
            return;
        }

        $stub = $this->stubPath('docs/deptrac.md.stub');
        $content = $this->renderer->render($stub, ['date' => date('Y-m-d')]);
        $this->filesystem->put($file, $content);
        $this->line("Generated: {$file}");
    }

    protected function generateModulesOverview(string $docsPath): void
    {
        $file = $docsPath . '/modules.md';

        if ($this->filesystem->exists($file) && !$this->option('force')) {
            $this->warn("Exists (skipping): {$file}");
            return;
        }

        $stub = $this->stubPath('docs/modules.md.stub');
        $modules = $this->config->modules();
        $links = array_map(fn ($m) => '- [' . $m . '](modules/' . Str::kebab($m) . '.md)', $modules);

        $content = $this->renderer->render($stub, [
            'modules_list' => implode("\n", $links),
            'date' => date('Y-m-d'),
        ]);

        $this->filesystem->put($file, $content);
        $this->line("Generated: {$file}");
    }

    protected function generateModuleDoc(string $module, string $docsPath): void
    {
        $file = $docsPath . '/modules/' . Str::kebab($module) . '.md';

        if ($this->filesystem->exists($file) && !$this->option('force')) {
            $this->warn("Exists (skipping): {$file}");
            return;
        }

        $content = $this->docsGenerator->generate($module);
        $this->filesystem->put($file, $content);
        $this->line("Generated: {$file}");
    }

    protected function stubPath(string $stub): string
    {
        return dirname(__DIR__, 2) . '/stubs/' . $stub;
    }
}
