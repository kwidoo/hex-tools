<?php

namespace Kwidoo\HexTools\Generators;

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Support\StubRenderer;
use Kwidoo\HexTools\Support\Str;

class ModuleDocsGenerator
{
    public function __construct(
        protected HexToolsConfig $config,
        protected StubRenderer $renderer
    ) {}

    public function generate(string $module): string
    {
        $stub = $this->stubPath('docs/module.md.stub');
        $rules = $this->config->moduleRules();
        $allowed = $rules[$module] ?? [$module];
        $forbidden = array_values(array_diff($this->config->modules(), $allowed));

        return $this->renderer->render($stub, [
            'module' => $module,
            'module_kebab' => Str::kebab($module),
            'modules_list' => implode("\n", array_map(fn ($m) => "- {$m}", $this->config->modules())),
            'allowed_dependencies' => implode("\n", array_map(fn ($d) => "- {$d}", $allowed)),
            'forbidden_dependencies' => implode("\n", array_map(fn ($d) => "- {$d}", $forbidden)),
            'date' => date('Y-m-d'),
        ]);
    }

    protected function stubPath(string $stub): string
    {
        return dirname(__DIR__, 2) . '/stubs/' . $stub;
    }
}
