<?php

namespace Kwidoo\HexTools\Generators;

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Support\StubRenderer;

class MermaidLayerGraphGenerator
{
    public function __construct(
        protected HexToolsConfig $config,
        protected StubRenderer $renderer
    ) {}

    public function generate(): string
    {
        $stub = dirname(__DIR__, 2) . '/stubs/mermaid/layer-graph.md.stub';

        return $this->renderer->render($stub, [
            'diagram' => $this->buildDiagram(),
            'date' => date('Y-m-d'),
        ]);
    }

    protected function buildDiagram(): string
    {
        $configuredLayers = array_map('ucfirst', array_keys($this->config->layers()));
        $ruleset = $this->ruleset();

        $lines = ['graph TD'];

        foreach ($ruleset as $layer => $deps) {
            if (!in_array($layer, $configuredLayers, true)) {
                continue;
            }

            if (empty($deps)) {
                $lines[] = "    {$layer}";
                continue;
            }

            foreach ($deps as $dep) {
                if (in_array($dep, $configuredLayers, true)) {
                    $lines[] = "    {$layer} --> {$dep}";
                }
            }
        }

        return implode("\n", $lines);
    }

    protected function ruleset(): array
    {
        return [
            'Domain' => [],
            'Application' => ['Domain'],
            'Http' => ['Application', 'Domain'],
            'Infrastructure' => ['Application', 'Domain', 'Models'],
            'Models' => ['Domain'],
            'Support' => [],
            'Console' => ['Application', 'Domain'],
            'Providers' => ['Domain', 'Application', 'Http', 'Infrastructure', 'Models', 'Support', 'Console'],
        ];
    }
}
