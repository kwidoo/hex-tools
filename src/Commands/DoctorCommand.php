<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Application\Analysis\ArchitectureDoctor;
use Kwidoo\HexTools\Application\Reports\ArchitectureReportFormatter;

class DoctorCommand extends Command
{
    protected $signature = 'hex:doctor
        {--format=table : Output format: table|json|md}
        {--strict : Treat warnings as failures}';

    protected $description = 'Check hex-tools configuration and architecture tooling health.';

    public function __construct(
        protected ArchitectureDoctor $doctor,
        protected ArchitectureReportFormatter $formatter
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $report = $this->doctor->diagnose();
        $this->writeFormatted($this->formatter->doctor($report, $this->option('format')));

        return $report->hasFailures($this->option('strict')) ? self::FAILURE : self::SUCCESS;
    }

    protected function writeFormatted(string $content): void
    {
        foreach (explode("\n", rtrim($content)) as $line) {
            $this->line($line);
        }
    }
}
