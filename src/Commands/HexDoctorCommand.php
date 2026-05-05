<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Reports\CommandResult;
use Kwidoo\HexTools\Reports\ConfigStatus;
use Kwidoo\HexTools\Reports\DoctorReport;
use Kwidoo\HexTools\Reports\ToolStatus;
use Kwidoo\HexTools\Support\ProcessRunner;
use Kwidoo\HexTools\Support\ToolAvailability;

class HexDoctorCommand extends Command
{
    protected $signature = 'hex:doctor
        {--json : Output JSON instead of table}
        {--strict : Return non-zero exit code if important tools/configs are missing}
        {--run-checks : Run available quality tools and summarize results}';

    protected $description = 'Check the architecture tooling state of the project.';

    public function __construct(
        protected ToolAvailability $toolAvailability,
        protected ProcessRunner $processRunner
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $report = $this->buildReport();

        if ($this->option('run-checks')) {
            $report->results = $this->runChecks($report);
        }

        if ($this->option('json')) {
            $this->outputJson($report);
        } else {
            $this->outputTable($report);
        }

        if ($this->option('strict') && $report->hasMissingImportantItems()) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function buildReport(): DoctorReport
    {
        $report = new DoctorReport();

        $report->tools = [
            new ToolStatus('Deptrac', $this->toolAvailability->hasDeptrac(), 'vendor/bin/deptrac'),
            new ToolStatus('PHPStan', $this->toolAvailability->hasPhpStan(), 'vendor/bin/phpstan'),
            new ToolStatus('PHPMD', $this->toolAvailability->hasPhpMd(), 'vendor/bin/phpmd'),
            new ToolStatus('Pint', $this->toolAvailability->hasPint(), 'vendor/bin/pint'),
            new ToolStatus('Rector', $this->toolAvailability->hasRector(), 'vendor/bin/rector'),
            new ToolStatus('Composer audit', true, 'composer audit'),
        ];

        $configs = [
            'deptrac.layers.yaml', 'deptrac.modules.yaml',
            'phpstan.neon.dist', 'phpstan-domain.neon', 'phpstan-application.neon',
            'phpmd.xml', 'pint.json', 'rector.php',
        ];
        foreach ($configs as $file) {
            $report->configs[] = new ConfigStatus($file, $file, file_exists(base_path($file)));
        }

        $docs = [
            'docs/architecture/deptrac.md',
            'docs/architecture/static-analysis.md',
            'docs/architecture/phpmd.md',
            'docs/architecture/modules.md',
        ];
        foreach ($docs as $file) {
            $report->docs[] = new ConfigStatus($file, $file, file_exists(base_path($file)));
        }

        $folders = [
            'app/Domain', 'app/Application', 'app/Infrastructure',
            'app/Http', 'app/Models',
            'docs/architecture', 'docs/architecture/modules', 'docs/adr',
            'build/architecture',
        ];
        foreach ($folders as $folder) {
            $report->folders[] = new ConfigStatus($folder, $folder, is_dir(base_path($folder)));
        }

        $report->suggestions = $this->buildSuggestions($report);

        return $report;
    }

    protected function buildSuggestions(DoctorReport $report): array
    {
        $steps = [];

        foreach ($report->tools as $tool) {
            if (!$tool->installed && $tool->name !== 'Composer audit') {
                $steps[] = match ($tool->name) {
                    'Deptrac' => 'Run php artisan hex:install --composer-scripts',
                    'PHPStan' => 'Run php artisan hex:phpstan:install',
                    'PHPMD' => 'Run php artisan hex:phpmd:install',
                    'Pint' => 'Run php artisan hex:pint:install',
                    'Rector' => 'Run php artisan hex:rector:install',
                    default => "Install {$tool->name}",
                };
            }
        }

        $missingConfigs = array_filter($report->configs, fn (ConfigStatus $c) => !$c->exists);
        if (count($missingConfigs) > 0) {
            $steps[] = 'Run php artisan hex:quality:install --profile=strict';
        }

        if (empty($steps)) {
            $steps[] = 'Run php artisan hex:report Product';
        }

        return $steps;
    }

    /** @return CommandResult[] */
    protected function runChecks(DoctorReport $report): array
    {
        $results = [];
        $checks = [];

        $toolMap = array_column($report->tools, null, 'name');

        if (($toolMap['Deptrac']->installed ?? false) && file_exists(base_path('deptrac.layers.yaml'))) {
            $checks[] = ['Deptrac (layers)', 'vendor/bin/deptrac analyse --config-file=deptrac.layers.yaml'];
        }
        if (($toolMap['Deptrac']->installed ?? false) && file_exists(base_path('deptrac.modules.yaml'))) {
            $checks[] = ['Deptrac (modules)', 'vendor/bin/deptrac analyse --config-file=deptrac.modules.yaml'];
        }
        if (($toolMap['PHPStan']->installed ?? false) && file_exists(base_path('phpstan.neon.dist'))) {
            $checks[] = ['PHPStan', 'vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=1G'];
        }
        if (($toolMap['PHPMD']->installed ?? false) && file_exists(base_path('phpmd.xml'))) {
            $checks[] = ['PHPMD', 'vendor/bin/phpmd app text phpmd.xml'];
        }
        if ($toolMap['Pint']->installed ?? false) {
            $checks[] = ['Pint', 'vendor/bin/pint --test'];
        }
        if (($toolMap['Rector']->installed ?? false) && file_exists(base_path('rector.php'))) {
            $checks[] = ['Rector', 'vendor/bin/rector process --dry-run'];
        }

        foreach ($checks as [$tool, $command]) {
            $output = '';
            $exitCode = $this->processRunner->run($command, function (string $type, string $buffer) use (&$output) {
                $output .= $buffer;
            });

            $results[] = new CommandResult($tool, $command, $exitCode, trim($output));
        }

        return $results;
    }

    protected function outputTable(DoctorReport $report): void
    {
        $this->line('');
        $this->line('<options=bold>Hex Tools Doctor</>');
        $this->line('');

        $this->line('<options=bold>Tools:</>');
        foreach ($report->tools as $tool) {
            $status = $tool->installed ? '<fg=green>installed</>' : '<fg=yellow>missing</>';
            $this->line("  {$this->pad($tool->name . ':')} {$status}");
        }

        $this->line('');
        $this->line('<options=bold>Configs:</>');
        foreach ($report->configs as $config) {
            $status = $config->exists ? '<fg=green>exists</>' : '<fg=yellow>missing</>';
            $this->line("  {$this->pad($config->name . ':')} {$status}");
        }

        $this->line('');
        $this->line('<options=bold>Architecture docs:</>');
        foreach ($report->docs as $doc) {
            $status = $doc->exists ? '<fg=green>exists</>' : '<fg=yellow>missing</>';
            $this->line("  {$this->pad($doc->name . ':')} {$status}");
        }

        if (!empty($report->results)) {
            $this->line('');
            $this->line('<options=bold>Check Results:</>');
            foreach ($report->results as $result) {
                $status = $result->passed() ? '<fg=green>passed</>' : '<fg=red>failed</>';
                $this->line("  {$this->pad($result->tool . ':')} {$status}");
            }
        }

        if (!empty($report->suggestions)) {
            $this->line('');
            $this->line('<options=bold>Recommended next steps:</>');
            foreach (array_values($report->suggestions) as $i => $step) {
                $this->line('  ' . ($i + 1) . '. ' . $step);
            }
        }

        $this->line('');
    }

    protected function outputJson(DoctorReport $report): void
    {
        $data = [
            'tools' => array_map(fn (ToolStatus $t) => [
                'name' => $t->name,
                'installed' => $t->installed,
                'binary' => $t->binary,
            ], $report->tools),
            'configs' => array_map(fn (ConfigStatus $c) => [
                'name' => $c->name,
                'exists' => $c->exists,
            ], $report->configs),
            'docs' => array_map(fn (ConfigStatus $c) => [
                'name' => $c->name,
                'exists' => $c->exists,
            ], $report->docs),
            'suggestions' => array_values($report->suggestions),
            'results' => array_map(fn (CommandResult $r) => [
                'tool' => $r->tool,
                'command' => $r->command,
                'exit_code' => $r->exitCode,
                'passed' => $r->passed(),
            ], $report->results),
        ];

        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    protected function pad(string $text, int $width = 36): string
    {
        return str_pad($text, $width);
    }
}
