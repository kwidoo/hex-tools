<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Support\ProcessRunner;
use Kwidoo\HexTools\Support\ToolAvailability;

class RunPhpMdCommand extends Command
{
    protected $signature = 'hex:phpmd:run
        {--path=app : Source path}
        {--format=text : Report format}
        {--ruleset=phpmd.xml : PHPMD ruleset file}
        {--baseline=phpmd.baseline.xml : Optional baseline file}
        {--reportfile= : Optional report output file}';

    protected $description = 'Run PHPMD using generated rules.';

    public function __construct(
        protected ProcessRunner $processRunner,
        protected ToolAvailability $toolAvailability
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!$this->toolAvailability->hasPhpMd()) {
            $this->error('PHPMD is not installed.');
            $this->line('');
            $this->line('Install it with:');
            $this->line('');
            $this->line('  composer require --dev phpmd/phpmd');
            $this->line('');
            return self::FAILURE;
        }

        $path = (string) $this->option('path');
        $format = (string) $this->option('format');
        $ruleset = (string) $this->option('ruleset');
        $baseline = (string) $this->option('baseline');
        $reportfile = (string) $this->option('reportfile');

        $command = "vendor/bin/phpmd {$path} {$format} {$ruleset}";

        if (file_exists(base_path($baseline))) {
            $command .= " --baseline-file {$baseline}";
        }

        if ($reportfile !== '') {
            $command .= " --reportfile {$reportfile}";
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
