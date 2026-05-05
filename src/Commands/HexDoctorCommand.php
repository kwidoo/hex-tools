<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Domain\Architecture\DoctorCheck;
use Kwidoo\HexTools\Domain\Architecture\DoctorReport;
use Kwidoo\HexTools\Support\ProcessRunner;
use Kwidoo\HexTools\Support\ToolAvailability;

class HexDoctorCommand extends Command
{
    protected $signature = 'hex:doctor
        {--format=table : Output format: table or json}
        {--strict : Exit with failure code when warnings/errors are found}
        {--run-checks : Run external quality checks when available}';

    protected $description = 'Check the architecture tooling state of the project.';

    public function __construct(
        protected ToolAvailability $toolAvailability,
        protected ProcessRunner $processRunner
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $checks = $this->buildChecks();

        if ($this->option('run-checks')) {
            $checks = array_merge($checks, $this->runChecks());
        }

        $report = new DoctorReport($checks);

        if ($this->option('format') === 'json') {
            $this->outputJson($report);
        } else {
            $this->outputTable($report);
        }

        if ($this->option('strict') && $report->hasFailures(true)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /** @return DoctorCheck[] */
    protected function buildChecks(): array
    {
        $checks = [];

        // Tools
        $tools = [
            ['Deptrac', $this->toolAvailability->hasDeptrac(), 'vendor/bin/deptrac'],
            ['PHPStan', $this->toolAvailability->hasPhpStan(), 'vendor/bin/phpstan'],
            ['PHPMD', $this->toolAvailability->hasPhpMd(), 'vendor/bin/phpmd'],
            ['Pint', $this->toolAvailability->hasPint(), 'vendor/bin/pint'],
            ['Rector', $this->toolAvailability->hasRector(), 'vendor/bin/rector'],
            ['Composer audit', true, 'composer audit'],
        ];

        foreach ($tools as [$name, $installed, $binary]) {
            $checks[] = new DoctorCheck(
                $installed ? 'ok' : 'warn',
                strtolower(str_replace(' ', '_', $name)),
                $installed ? "{$name} is installed" : "{$name} is missing",
                $installed ? null : $binary
            );
        }

        // Configs
        $configs = [
            'deptrac.layers.yaml', 'deptrac.modules.yaml',
            'phpstan.neon.dist', 'phpstan-domain.neon', 'phpstan-application.neon',
            'phpmd.xml', 'pint.json', 'rector.php',
        ];
        foreach ($configs as $file) {
            $exists = file_exists(base_path($file));
            $checks[] = new DoctorCheck(
                $exists ? 'ok' : 'warn',
                'config_' . strtolower(str_replace('.', '_', $file)),
                $exists ? "Config {$file} exists" : "Config {$file} is missing",
                $exists ? null : base_path($file)
            );
        }

        // Docs
        $docs = [
            'docs/architecture/deptrac.md',
            'docs/architecture/static-analysis.md',
            'docs/architecture/phpmd.md',
            'docs/architecture/modules.md',
        ];
        foreach ($docs as $file) {
            $exists = file_exists(base_path($file));
            $checks[] = new DoctorCheck(
                $exists ? 'ok' : 'info',
                'doc_' . strtolower(str_replace('/', '_', str_replace('.', '_', $file))),
                $exists ? "Doc {$file} exists" : "Doc {$file} is missing",
                $exists ? null : base_path($file)
            );
        }

        // Folders
        $folders = [
            'app/Domain', 'app/Application', 'app/Infrastructure',
            'app/Http', 'app/Models',
            'docs/architecture', 'docs/architecture/modules', 'docs/adr',
            'build/architecture',
        ];
        foreach ($folders as $folder) {
            $exists = is_dir(base_path($folder));
            $checks[] = new DoctorCheck(
                $exists ? 'ok' : 'warn',
                'folder_' . strtolower(str_replace('/', '_', $folder)),
                $exists ? "Folder {$folder} exists" : "Folder {$folder} is missing",
                $exists ? null : base_path($folder)
            );
        }

        return $checks;
    }

    /** @return DoctorCheck[] */
    protected function runChecks(): array
    {
        $checks = [];
        $toolChecks = $this->buildChecks();
        
        $toolMap = [];
        foreach ($toolChecks as $check) {
            if (str_starts_with($check->code, 'deptrac') || str_starts_with($check->code, 'phpstan') || 
                str_starts_with($check->code, 'phpmd') || str_starts_with($check->code, 'pint') || 
                str_starts_with($check->code, 'rector')) {
                $toolMap[strtolower(explode('_', $check->code)[0])] = $check->status === 'ok';
            }
        }

        $checkCommands = [];

        if (($toolMap['deptrac'] ?? false) && file_exists(base_path('deptrac.layers.yaml'))) {
            $checkCommands[] = ['Deptrac (layers)', 'vendor/bin/deptrac analyse --config-file=deptrac.layers.yaml'];
        }
        if (($toolMap['deptrac'] ?? false) && file_exists(base_path('deptrac.modules.yaml'))) {
            $checkCommands[] = ['Deptrac (modules)', 'vendor/bin/deptrac analyse --config-file=deptrac.modules.yaml'];
        }
        if (($toolMap['phpstan'] ?? false) && file_exists(base_path('phpstan.neon.dist'))) {
            $checkCommands[] = ['PHPStan', 'vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=1G'];
        }
        if (($toolMap['phpmd'] ?? false) && file_exists(base_path('phpmd.xml'))) {
            $checkCommands[] = ['PHPMD', 'vendor/bin/phpmd app text phpmd.xml'];
        }
        if (($toolMap['pint'] ?? false)) {
            $checkCommands[] = ['Pint', 'vendor/bin/pint --test'];
        }
        if (($toolMap['rector'] ?? false) && file_exists(base_path('rector.php'))) {
            $checkCommands[] = ['Rector', 'vendor/bin/rector process --dry-run'];
        }

        foreach ($checkCommands as [$tool, $command]) {
            $output = '';
            $exitCode = $this->processRunner->run($command, function (string $type, string $buffer) use (&$output) {
                $output .= $buffer;
            });

            $checks[] = new DoctorCheck(
                $exitCode === 0 ? 'ok' : 'fail',
                'check_' . strtolower(str_replace(' ', '_', $tool)),
                $exitCode === 0 ? "{$tool} check passed" : "{$tool} check failed",
                null
            );
        }

        return $checks;
    }

    protected function outputTable(DoctorReport $report): void
    {
        $this->line('');
        $this->line('<options=bold>Hex Tools Doctor</>');
        $this->line('');

        $summary = $report->summary();
        $this->line("<options=bold>Summary:</>");
        $this->line("  Total checks: {$summary['checks']}");
        $this->line("  <fg=green>OK:</> {$summary['ok']}");
        $this->line("  <fg=yellow>Warnings:</> {$summary['warnings']}");
        $this->line("  <fg=red>Failures:</> {$summary['failures']}");
        $this->line('');

        $this->line('<options=bold>Checks:</>');
        foreach ($report->checks as $check) {
            $statusColor = match ($check->status) {
                'ok' => 'green',
                'warn' => 'yellow',
                'fail' => 'red',
                default => 'blue',
            };
            $statusLabel = strtoupper($check->status);
            $this->line("  [{$statusLabel}] {$check->message}");
        }

        $this->line('');
    }

    protected function outputJson(DoctorReport $report): void
    {
        $this->line(json_encode($report->toArray(), JSON_PRETTY_PRINT));
    }
}
