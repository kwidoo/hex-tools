<?php

namespace Kwidoo\HexTools\Generators;

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Support\StubRenderer;

class PintConfigGenerator
{
    public function __construct(
        protected HexToolsConfig $config,
        protected StubRenderer $renderer
    ) {}

    public function generate(): string
    {
        $preset = $this->config->get('pint.preset', 'laravel');

        return $this->renderer->render(
            $this->stubPath('pint.json.stub'),
            ['preset' => $preset]
        );
    }

    protected function stubPath(string $stub): string
    {
        return dirname(__DIR__, 2) . '/stubs/' . $stub;
    }
}
