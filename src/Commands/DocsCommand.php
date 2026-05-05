<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Application\Analysis\ArchitectureDocumentationGenerator;
use Kwidoo\HexTools\Config\HexToolsConfig;

class DocsCommand extends Command
{
    protected $signature = 'hex:docs
        {--output= : Output directory}
        {--format=md : Documentation format}
        {--module= : Generate documentation only for one module}
        {--force : Overwrite existing generated docs}';

    protected $description = 'Generate architecture intelligence documentation.';

    public function __construct(
        protected HexToolsConfig $config,
        protected ArchitectureDocumentationGenerator $generator
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!in_array($this->option('format'), ['md', 'markdown'], true)) {
            $this->error('Only markdown documentation is supported.');
            return self::FAILURE;
        }

        $output = $this->option('output') ?: $this->config->get('docs.output_path', $this->config->get('paths.docs', base_path('docs/architecture')));
        $written = $this->generator->generate($output, $this->option('module') ?: null, $this->option('force'));

        foreach ($written as $file) {
            $this->line("Generated: {$file}");
        }

        if ($written === []) {
            $this->warn('No files written. Existing docs were left unchanged.');
        }

        return self::SUCCESS;
    }
}
