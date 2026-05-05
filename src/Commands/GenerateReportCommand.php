<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Generators\ArchitectureReportGenerator;
use Kwidoo\HexTools\Reports\CommandResult;
use Kwidoo\HexTools\Support\Filesystem;
use Kwidoo\HexTools\Support\ProcessRunner;
use Kwidoo\HexTools\Support\ToolAvailability;

class GenerateReportCommand extends Command
{
    protected $signature = 'hex:report
        {module? : Optional module name}
        {--output= : Output markdown file}
        {--run-checks : Run tools and include results}
        {--format=markdown : markdown|json}';

    protected $description = 'Generate an architecture quality report.';

    public function __construct(
        protected ArchitectureReportGenerator $reportGenerator,
        protected ProcessRunner $processRunner,
        protected ToolAvailability $toolAvailability,
        protected Filesystem $filesystem
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $module = (string) $this->argument('module');
        $format = (string) $this->option('format');

        $checkResults = [];
        if ($this->option('run-checks')) {
            $checkResults = $this->runChecks();
        }

        $content = $this->reportGenerator->generate($module, $checkResults);

        $outputPath = $this->resolveOutputPath($module);

        $this->filesystem->ensureDirectory(dirname($outputPath));
        file_put_contents($outputPath, $content);

        $relative = str_replace(base_path('/'), '', $outputPath);
        $this->info("Report generated: {$relative}");

        if ($format === 'json') {
            $this->outputJson($module, $checkResults);
        }

        return self::SUCCESS;
    }

    protected function resolveOutputPath(string $module): string
    {
        if ($this->option('output')) {
            return base_path((string) $this->option('output'));
        }

        $reportsDir = base_path('build/architecture/reports');

        if ($module) {
            $slug = strtolower($module);
            return "{$reportsDir}/{$slug}-report.md";
        }

        return "{$reportsDir}/architecture-report.md";
    }

    /** @return CommandResult[] */
    protected function runChecks(): array
    {
        $results = [];
        $checks = [];

        if ($this->toolAvailability->hasDeptrac() && file_exists(base_path('deptrac.layers.yaml'))) {
            $checks[] = ['Deptrac (layers)', 'vendor/bin/deptrac analyse --config-file=deptrac.layers.yaml'];
        }
        if ($this->toolAvailability->hasDeptrac() && file_exists(base_path('deptrac.modules.yaml'))) {
            $checks[] = ['Deptrac (modules)', 'vendor/bin/deptrac analyse --config-file=deptrac.modules.yaml'];
        }
        if ($this->toolAvailability->hasPhpStan() && file_exists(base_path('phpstan.neon.dist'))) {
            $checks[] = ['PHPStan', 'vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=1G'];
        }
        if ($this->toolAvailability->hasPhpMd() && file_exists(base_path('phpmd.xml'))) {
            $checks[] = ['PHPMD', 'vendor/bin/phpmd app text phpmd.xml'];
        }

        foreach ($checks as [$tool, $command]) {
            $this->line("Running {$tool}...");
            $output = '';
            $exitCode = $this->processRunner->run($command, function (string $type, string $buffer) use (&$output) {
                $output .= $buffer;
            });
            $results[] = new CommandResult($tool, $command, $exitCode, trim($output));
        }

        return $results;
    }

    protected function outputJson(string $module, array $checkResults): void
    {
        $data = [
            'module' => $module ?: 'all',
            'generated_at' => date('Y-m-d H:i:s'),
            'checks' => array_map(fn (CommandResult $r) => [
                'tool' => $r->tool,
                'passed' => $r->passed(),
                'exit_code' => $r->exitCode,
            ], $checkResults),
        ];

        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }
}
