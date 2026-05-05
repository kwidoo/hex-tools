<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Generators\PintConfigGenerator;

class GeneratePintCommand extends Command
{
    protected $signature = 'hex:pint:generate
        {--output=pint.json : Output path}
        {--force : Overwrite existing file}';

    protected $description = 'Generate or regenerate pint.json.';

    public function __construct(protected PintConfigGenerator $generator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $output = (string) $this->option('output');
        $force = (bool) $this->option('force');
        $fullPath = base_path($output);

        if (file_exists($fullPath) && !$force) {
            $this->error("File exists (use --force to overwrite): {$output}");
            return self::FAILURE;
        }

        file_put_contents($fullPath, $this->generator->generate());
        $this->line("Generated: {$output}");

        return self::SUCCESS;
    }
}
