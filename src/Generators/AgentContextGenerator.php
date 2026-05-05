<?php

namespace Kwidoo\HexTools\Generators;

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Support\Str;
use Kwidoo\HexTools\Support\StubRenderer;
use Kwidoo\HexTools\Support\ToolAvailability;

class AgentContextGenerator
{
    public function __construct(
        protected HexToolsConfig $config,
        protected StubRenderer $renderer,
        protected ToolAvailability $toolAvailability
    ) {}

    public function generateGeneral(): string
    {
        return $this->renderer->render(
            $this->stubPath('docs/ai-context.md.stub'),
            [
                'generated_at' => date('Y-m-d H:i:s'),
                'modules_list' => $this->buildModulesList(),
                'tool_statuses' => $this->buildToolStatuses(),
            ]
        );
    }

    public function generateModuleContext(string $module): string
    {
        $rules = $this->config->moduleRules();
        $allowed = $rules[$module] ?? [$module];
        $forbidden = array_values(array_diff($this->config->modules(), $allowed));

        return $this->renderer->render(
            $this->stubPath('docs/module-ai-context.md.stub'),
            [
                'module' => $module,
                'module_kebab' => Str::kebab($module),
                'generated_at' => date('Y-m-d H:i:s'),
                'allowed_dependencies' => implode("\n", array_map(fn ($d) => "- `{$d}`", $allowed)),
                'forbidden_dependencies' => implode("\n", array_map(fn ($d) => "- `{$d}`", $forbidden)),
            ]
        );
    }

    protected function buildModulesList(): string
    {
        $modules = $this->config->modules();
        $rules = $this->config->moduleRules();

        $lines = [];
        foreach ($modules as $module) {
            $allowed = $rules[$module] ?? [$module];
            $lines[] = "- **{$module}** — may depend on: " . implode(', ', $allowed);
        }

        return implode("\n", $lines);
    }

    protected function buildToolStatuses(): string
    {
        $tools = [
            'Deptrac' => $this->toolAvailability->hasDeptrac(),
            'PHPStan' => $this->toolAvailability->hasPhpStan(),
            'PHPMD' => $this->toolAvailability->hasPhpMd(),
            'Pint' => $this->toolAvailability->hasPint(),
            'Rector' => $this->toolAvailability->hasRector(),
        ];

        $lines = [];
        foreach ($tools as $name => $installed) {
            $status = $installed ? 'installed' : 'not installed';
            $lines[] = "- **{$name}**: {$status}";
        }

        return implode("\n", $lines);
    }

    protected function stubPath(string $stub): string
    {
        return dirname(__DIR__, 2) . '/stubs/' . $stub;
    }
}
