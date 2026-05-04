<?php

namespace Kwidoo\HexTools\Generators;

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Support\StubRenderer;
use Kwidoo\HexTools\Support\Str;

class AdrGenerator
{
    public function __construct(
        protected HexToolsConfig $config,
        protected StubRenderer $renderer
    ) {}

    public function generate(string $title, string $status): string
    {
        $stub = $this->stubPath('docs/adr.md.stub');
        $number = $this->getNextNumber();

        return $this->renderer->render($stub, [
            'adr_number' => str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'adr_title' => $title,
            'adr_slug' => Str::slug($title),
            'adr_status' => ucfirst($status),
            'date' => date('Y-m-d'),
        ]);
    }

    public function getNextNumber(): int
    {
        $adrPath = $this->config->get('paths.adr');

        if (!is_dir($adrPath)) {
            return 1;
        }

        $files = glob($adrPath . '/*.md') ?: [];
        $numbers = [];

        foreach ($files as $file) {
            if (preg_match('/^(\d+)-/', basename($file), $matches)) {
                $numbers[] = (int) $matches[1];
            }
        }

        return empty($numbers) ? 1 : max($numbers) + 1;
    }

    public function getFilename(string $title, int $number): string
    {
        return str_pad((string) $number, 4, '0', STR_PAD_LEFT) . '-' . Str::slug($title) . '.md';
    }

    protected function stubPath(string $stub): string
    {
        return dirname(__DIR__, 2) . '/stubs/' . $stub;
    }
}
