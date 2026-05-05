<?php

namespace Kwidoo\HexTools\Generators;

use Kwidoo\HexTools\Support\StubRenderer;

class CiGenerator
{
    public function __construct(protected StubRenderer $renderer) {}

    public function generateGithub(): string
    {
        return file_get_contents($this->stubPath('github-quality.yml.stub'));
    }

    public function generateDrone(): string
    {
        return file_get_contents($this->stubPath('drone-quality.yml.stub'));
    }

    public function generateDocs(): string
    {
        return file_get_contents($this->stubPath('docs/ci.md.stub'));
    }

    protected function stubPath(string $stub): string
    {
        return dirname(__DIR__, 2) . '/stubs/' . $stub;
    }
}
