<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Generators\PhpMdRulesetGenerator;

class GeneratePhpMdCommand extends Command
{
    protected $signature = 'hex:phpmd:generate
        {--output=phpmd.xml : PHPMD ruleset output path}
        {--force : Overwrite existing file}';

    protected $description = 'Generate or regenerate PHPMD ruleset.';

    public function __construct(protected PhpMdRulesetGenerator $generator)
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
