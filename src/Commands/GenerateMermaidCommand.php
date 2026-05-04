<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Generators\MermaidLayerGraphGenerator;
use Kwidoo\HexTools\Generators\MermaidModuleGraphGenerator;
use Kwidoo\HexTools\Support\Filesystem;

class GenerateMermaidCommand extends Command
{
    protected $signature = 'hex:mermaid:generate
        {--force : Overwrite existing files}
        {--type= : Generate only "modules" or "layers"}';

    protected $description = 'Generate Mermaid architecture diagrams from config.';

    public function __construct(
        protected HexToolsConfig $config,
        protected Filesystem $filesystem,
        protected MermaidModuleGraphGenerator $moduleGraph,
        protected MermaidLayerGraphGenerator $layerGraph,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $docsPath = $this->config->get('paths.docs');
        $type = $this->option('type');

        $this->filesystem->ensureDirectory($docsPath);

        if ($type === null || $type === 'modules') {
            $this->write($docsPath . '/module-graph.md', fn () => $this->moduleGraph->generate());
        }

        if ($type === null || $type === 'layers') {
            $this->write($docsPath . '/layer-graph.md', fn () => $this->layerGraph->generate());
        }

        $this->info('Mermaid diagrams generated.');

        return self::SUCCESS;
    }

    protected function write(string $file, callable $generate): void
    {
        if ($this->filesystem->exists($file) && !$this->option('force')) {
            $this->warn("Exists (skipping): {$file}");
            return;
        }

        $this->filesystem->put($file, $generate());
        $this->line("Generated: {$file}");
    }
}
