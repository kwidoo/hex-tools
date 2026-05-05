<?php

namespace Kwidoo\HexTools\Generators;

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Support\StubRenderer;

class RectorConfigGenerator
{
    public function __construct(
        protected HexToolsConfig $config,
        protected StubRenderer $renderer
    ) {}

    public function generate(): string
    {
        $paths = $this->config->get('rector.paths', [
            'app/Domain',
            'app/Application',
            'app/Infrastructure',
            'app/Http',
        ]);

        $skip = $this->config->get('rector.skip', [
            'bootstrap',
            'database',
            'storage',
            'vendor',
        ]);

        return $this->renderer->render(
            $this->stubPath('rector.php.stub'),
            [
                'rector_paths' => $this->formatPaths($paths),
                'rector_skip' => $this->formatPaths($skip),
            ]
        );
    }

    public function generateDocs(): string
    {
        $paths = $this->config->get('rector.paths', [
            'app/Domain',
            'app/Application',
            'app/Infrastructure',
            'app/Http',
        ]);

        $skip = $this->config->get('rector.skip', [
            'bootstrap',
            'database',
            'storage',
            'vendor',
        ]);

        return $this->renderer->render(
            $this->stubPath('docs/rector.md.stub'),
            [
                'rector_paths' => implode("\n", array_map(fn ($p) => "- `{$p}`", $paths)),
                'rector_skip' => implode("\n", array_map(fn ($p) => "- `{$p}`", $skip)),
            ]
        );
    }

    protected function formatPaths(array $paths): string
    {
        return implode("\n", array_map(
            fn ($p) => "        __DIR__ . '/{$p}',",
            $paths
        ));
    }

    protected function stubPath(string $stub): string
    {
        return dirname(__DIR__, 2) . '/stubs/' . $stub;
    }
}
