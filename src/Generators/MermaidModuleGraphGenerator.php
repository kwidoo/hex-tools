<?php

namespace Kwidoo\HexTools\Generators;

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Support\StubRenderer;

class MermaidModuleGraphGenerator
{
    public function __construct(
        protected HexToolsConfig $config,
        protected StubRenderer $renderer
    ) {}

    public function generate(): string
    {
        $stub = dirname(__DIR__, 2) . '/stubs/mermaid/module-graph.md.stub';

        return $this->renderer->render($stub, [
            'diagram' => $this->buildDiagram(),
            'date' => date('Y-m-d'),
        ]);
    }

    protected function buildDiagram(): string
    {
        $rules = $this->config->moduleRules();
        $lines = ['graph TD'];

        foreach ($rules as $module => $allowed) {
            $dependencies = array_values(array_filter($allowed, fn ($d) => $d !== $module));

            if (empty($dependencies)) {
                $lines[] = "    {$module}";
                continue;
            }

            foreach ($dependencies as $dep) {
                $lines[] = "    {$module} --> {$dep}";
            }
        }

        return implode("\n", $lines);
    }
}
