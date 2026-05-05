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
        {--output= : Output file path}
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

        if (!in_array($format, ['markdown', 'json'], true)) {
            $this->error("Unsupported format '{$format}'. Use 'markdown' or 'json'.");
            return self::FAILURE;
        }

        $checkResults = [];
        if ($this->option('run-checks')) {
            $checkResults = $this->runChecks();
        }

        $outputPath = $this->resolveOutputPath($module, $format);

        $content = $this->generateContent($module, $checkResults, $format);

        $this->filesystem->ensureDirectory(dirname($outputPath));
        file_put_contents($outputPath, $content);

        $relative = str_replace(base_path('/') . '', '', $outputPath);
        $this->info("Report generated: {$relative}");

        return self::SUCCESS;
    }

    protected function resolveOutputPath(string $module, string $format): string
    {
        if ($this->option('output')) {
            return base_path((string) $this->option('output'));
        }

        $reportsDir = base_path('build/architecture/reports');

        if ($module) {
            $slug = strtolower($module);
            return "{$reportsDir}/{$slug}-report.{$format}";
        }

        return "{$reportsDir}/architecture-report.{$format}";
    }

    protected function generateContent(string $module, array $checkResults, string $format): string
    {
        if ($format === 'json') {
            return $this->generateJson($module, $checkResults);
        }

        return $this->reportGenerator->generate($module, $checkResults);
    }

    protected function generateJson(string $module, array $checkResults): string
    {
        $data = [
            'module_name' => $module ?: 'all',
            'generated_at' => date('Y-m-d H:i:s'),
            'architecture_summary' => [
                'modules' => $this->reportGenerator->getModules(),
                'module_rules' => $this->reportGenerator->getModuleRules(),
            ],
            'violations' => [],
            'dependencies' => [],
            'quality_check_summary' => [
                'checks_run' => count($checkResults),
                'results' => array_map(fn (CommandResult $r) => [
                    'tool' => $r->tool,
                    'passed' => $r->passed(),
                    'exit_code' => $r->exitCode,
                ], $checkResults),
            ],
        ];

        return json_encode($data, JSON_PRETTY_PRINT);
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
}
