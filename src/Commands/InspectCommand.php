<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Application\Analysis\ArchitectureInspector;
use Kwidoo\HexTools\Application\Reports\ArchitectureReportFormatter;

class InspectCommand extends Command
{
    protected $signature = 'hex:inspect
        {--format=table : Output format: table|json|md}
        {--module= : Inspect only one module}
        {--fail-on-violations : Return a failing exit code when new failures are found}
        {--ignore-baseline : Do not annotate issues with the architecture baseline}';

    protected $description = 'Inspect module structure and architecture boundary issues.';

    public function __construct(
        protected ArchitectureInspector $inspector,
        protected ArchitectureReportFormatter $formatter
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $report = $this->inspector->inspect(
            $this->option('module') ?: null,
            !$this->option('ignore-baseline')
        );

        $this->writeFormatted($this->formatter->architecture($report, $this->option('format')));

        if ($this->option('fail-on-violations') && $report->hasNewFailures()) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function writeFormatted(string $content): void
    {
        foreach (explode("\n", rtrim($content)) as $line) {
            $this->line($line);
        }
    }
}
