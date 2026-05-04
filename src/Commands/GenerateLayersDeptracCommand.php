<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Generators\DeptracLayersGenerator;
use Kwidoo\HexTools\Support\Filesystem;

class GenerateLayersDeptracCommand extends Command
{
    protected $signature = 'hex:deptrac:layers
        {--output=deptrac.layers.yaml : Output file path}
        {--force : Overwrite existing file}';

    protected $description = 'Generate deptrac.layers.yaml for strict technical layer architecture checks.';

    public function __construct(
        protected DeptracLayersGenerator $generator,
        protected Filesystem $filesystem
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $output = base_path($this->option('output'));

        if ($this->filesystem->exists($output) && !$this->option('force')) {
            $this->error("File already exists: {$output}");
            $this->line('Use --force to overwrite.');
            return self::FAILURE;
        }

        if (!file_exists(base_path('vendor/bin/deptrac'))) {
            $this->warn('Deptrac is not installed. Run:');
            $this->line('composer require --dev qossmic/deptrac');
        }

        $content = $this->generator->generate();
        $this->filesystem->put($output, $content);

        $this->info("Generated: {$output}");

        return self::SUCCESS;
    }
}
