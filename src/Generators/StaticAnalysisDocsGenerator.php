<?php

namespace Kwidoo\HexTools\Generators;

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Support\StubRenderer;

class StaticAnalysisDocsGenerator
{
    public function __construct(
        protected HexToolsConfig $config,
        protected StubRenderer $renderer
    ) {}

    public function generate(): string
    {
        $phpstan = $this->config->get('phpstan', []);

        return $this->renderer->render(
            $this->stubPath('docs/static-analysis.md.stub'),
            [
                'main_level' => (string) ($phpstan['levels']['main'] ?? 5),
                'domain_level' => (string) ($phpstan['levels']['domain'] ?? 8),
                'application_level' => (string) ($phpstan['levels']['application'] ?? 7),
                'memory_limit' => $phpstan['memory_limit'] ?? '1G',
            ]
        );
    }

    protected function stubPath(string $stub): string
    {
        return dirname(__DIR__, 2) . '/stubs/' . $stub;
    }
}
