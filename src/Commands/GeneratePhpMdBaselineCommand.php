<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Support\ProcessRunner;
use Kwidoo\HexTools\Support\ToolAvailability;

class GeneratePhpMdBaselineCommand extends Command
{
    protected $signature = 'hex:phpmd:baseline
        {--run : Actually run PHPMD baseline generation}
        {--path=app : Source path}
        {--format=text : Output format}
        {--ruleset=phpmd.xml : PHPMD ruleset file}
        {--baseline=phpmd.baseline.xml : Baseline file}';

    protected $description = 'Print or run PHPMD baseline generation command.';

    public function __construct(
        protected ProcessRunner $processRunner,
        protected ToolAvailability $toolAvailability
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = (string) $this->option('path');
        $format = (string) $this->option('format');
        $ruleset = (string) $this->option('ruleset');
        $baseline = (string) $this->option('baseline');

        $command = "vendor/bin/phpmd {$path} {$format} {$ruleset} --generate-baseline --baseline-file {$baseline}";

        if (!$this->option('run')) {
            $this->line('Run the following command to generate a PHPMD baseline:');
            $this->line('');
            $this->line("  {$command}");
            $this->line('');
            return self::SUCCESS;
        }

        if (!$this->toolAvailability->hasPhpMd()) {
            $this->error('vendor/bin/phpmd not found.');
            $this->line('');
            $this->line('Install PHPMD with:');
            $this->line('');
            $this->line('  composer require --dev phpmd/phpmd');
            $this->line('');
            return self::FAILURE;
        }

        $this->line("Running: {$command}");

        $exitCode = $this->processRunner->run(
            $command,
            function (string $type, string $buffer) {
                $this->output->write($buffer);
            }
        );

        return $exitCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
