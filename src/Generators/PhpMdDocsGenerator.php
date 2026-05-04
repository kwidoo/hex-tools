<?php

namespace Kwidoo\HexTools\Generators;

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Support\StubRenderer;

class PhpMdDocsGenerator
{
    public function __construct(
        protected HexToolsConfig $config,
        protected StubRenderer $renderer
    ) {}

    public function generate(): string
    {
        $phpmd = $this->config->get('phpmd', []);
        $thresholds = $phpmd['thresholds'] ?? [];

        return $this->renderer->render(
            $this->stubPath('docs/phpmd.md.stub'),
            [
                'cyclomatic_complexity_report_level' => (string) ($thresholds['cyclomatic_complexity_report_level'] ?? 10),
                'npath_complexity_report_level' => (string) ($thresholds['npath_complexity_report_level'] ?? 200),
                'excessive_method_length_minimum' => (string) ($thresholds['excessive_method_length_minimum'] ?? 80),
                'excessive_class_length_minimum' => (string) ($thresholds['excessive_class_length_minimum'] ?? 400),
                'too_many_methods_maxmethods' => (string) ($thresholds['too_many_methods_maxmethods'] ?? 20),
                'too_many_public_methods_maxmethods' => (string) ($thresholds['too_many_public_methods_maxmethods'] ?? 15),
                'coupling_between_objects_maximum' => (string) ($thresholds['coupling_between_objects_maximum'] ?? 13),
            ]
        );
    }

    protected function stubPath(string $stub): string
    {
        return dirname(__DIR__, 2) . '/stubs/' . $stub;
    }
}
