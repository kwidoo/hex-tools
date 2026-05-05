<?php

namespace Kwidoo\HexTools\Generators;

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Reports\CommandResult;
use Kwidoo\HexTools\Reports\ConfigStatus;
use Kwidoo\HexTools\Reports\ToolStatus;
use Kwidoo\HexTools\Support\StubRenderer;
use Kwidoo\HexTools\Support\ToolAvailability;

class ArchitectureReportGenerator
{
    public function __construct(
        protected HexToolsConfig $config,
        protected StubRenderer $renderer,
        protected ToolAvailability $toolAvailability
    ) {}

    /**
     * @param CommandResult[] $checkResults
     */
    public function generate(string $module = '', array $checkResults = []): string
    {
        return $this->renderer->render(
            $this->stubPath('docs/architecture-report.md.stub'),
            [
                'generated_at' => date('Y-m-d H:i:s'),
                'module' => $module ?: 'All',
                'tool_statuses' => $this->buildToolStatuses(),
                'config_statuses' => $this->buildConfigStatuses(),
                'module_map' => $this->buildModuleMap($module),
                'suggested_steps' => $this->buildSuggestions(),
                'check_results' => $this->buildCheckResults($checkResults),
            ]
        );
    }

    protected function buildToolStatuses(): string
    {
        $tools = [
            new ToolStatus('Deptrac', $this->toolAvailability->hasDeptrac(), 'vendor/bin/deptrac'),
            new ToolStatus('PHPStan', $this->toolAvailability->hasPhpStan(), 'vendor/bin/phpstan'),
            new ToolStatus('PHPMD', $this->toolAvailability->hasPhpMd(), 'vendor/bin/phpmd'),
            new ToolStatus('Pint', $this->toolAvailability->hasPint(), 'vendor/bin/pint'),
            new ToolStatus('Rector', $this->toolAvailability->hasRector(), 'vendor/bin/rector'),
        ];

        $lines = ['| Tool | Status |', '|---|---|'];
        foreach ($tools as $tool) {
            $status = $tool->installed ? 'installed' : 'missing';
            $lines[] = "| {$tool->name} | {$status} |";
        }

        return implode("\n", $lines);
    }

    protected function buildConfigStatuses(): string
    {
        $files = [
            'deptrac.layers.yaml',
            'deptrac.modules.yaml',
            'phpstan.neon.dist',
            'phpmd.xml',
            'pint.json',
            'rector.php',
        ];

        $lines = ['| Config | Status |', '|---|---|'];
        foreach ($files as $file) {
            $exists = file_exists(base_path($file)) ? 'exists' : 'missing';
            $lines[] = "| `{$file}` | {$exists} |";
        }

        return implode("\n", $lines);
    }

    protected function buildModuleMap(string $module): string
    {
        $modules = $this->config->modules();
        $rules = $this->config->moduleRules();

        if ($module && in_array($module, $modules)) {
            $allowed = $rules[$module] ?? [$module];
            return "**{$module}** may depend on: " . implode(', ', $allowed);
        }

        $lines = [];
        foreach ($modules as $mod) {
            $allowed = $rules[$mod] ?? [$mod];
            $lines[] = "- **{$mod}**: " . implode(', ', $allowed);
        }

        return implode("\n", $lines);
    }

    protected function buildSuggestions(): string
    {
        $steps = [];
        $i = 1;

        if (!$this->toolAvailability->hasDeptrac()) {
            $steps[] = "{$i}. Install Deptrac: `composer require --dev deptrac/deptrac`";
            $i++;
        }
        if (!$this->toolAvailability->hasPhpStan()) {
            $steps[] = "{$i}. Install PHPStan: `composer require --dev larastan/larastan`";
            $i++;
        }
        if (!$this->toolAvailability->hasPhpMd()) {
            $steps[] = "{$i}. Install PHPMD: `composer require --dev phpmd/phpmd`";
            $i++;
        }
        if (!$this->toolAvailability->hasPint()) {
            $steps[] = "{$i}. Install Pint: `composer require --dev laravel/pint`";
            $i++;
        }
        if (!$this->toolAvailability->hasRector()) {
            $steps[] = "{$i}. Install Rector: `composer require --dev rector/rector`";
            $i++;
        }

        if (empty($steps)) {
            return "All tools are installed. Run `php artisan hex:doctor --run-checks` to validate the full stack.";
        }

        return implode("\n", $steps);
    }

    /**
     * @param CommandResult[] $results
     */
    protected function buildCheckResults(array $results): string
    {
        if (empty($results)) {
            return '';
        }

        $lines = ['## Check Results', '', '| Tool | Status | Command |', '|---|---|---|'];
        foreach ($results as $result) {
            $status = $result->passed() ? 'passed' : 'failed';
            $lines[] = "| {$result->tool} | {$status} | `{$result->command}` |";
        }

        return implode("\n", $lines);
    }

    protected function stubPath(string $stub): string
    {
        return dirname(__DIR__, 2) . '/stubs/' . $stub;
    }

    public function getModules(): array
    {
        return $this->config->modules();
    }

    public function getModuleRules(): array
    {
        return $this->config->moduleRules();
    }
}
