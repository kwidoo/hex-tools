<?php

namespace Kwidoo\HexTools\Generators;

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Support\StubRenderer;

class PhpStanConfigGenerator
{
    public function __construct(
        protected HexToolsConfig $config,
        protected StubRenderer $renderer
    ) {}

    public function generateMain(bool $includeBaseline = false): string
    {
        $phpstan = $this->config->get('phpstan', []);
        $includes = $phpstan['includes'] ?? ['vendor/larastan/larastan/extension.neon', 'vendor/nesbot/carbon/extension.neon'];
        $paths = $phpstan['paths']['main'] ?? ['app'];
        $level = $phpstan['levels']['main'] ?? 5;
        $tmpDir = $phpstan['tmp_dirs']['main'] ?? 'storage/framework/phpstan';
        $excludePaths = $phpstan['exclude_paths'] ?? ['database/migrations/*', 'database/seeders/*', 'bootstrap/*', 'storage/*'];
        $baseline = $phpstan['baseline'] ?? 'phpstan-baseline.neon';

        $baselineInclude = $includeBaseline ? "    - {$baseline}\n" : '';

        return $this->renderer->render(
            $this->stubPath('phpstan.neon.dist.stub'),
            [
                'includes' => $this->formatList($includes, '    '),
                'baseline_include' => $baselineInclude,
                'main_paths' => $this->formatList($paths, '        '),
                'main_level' => (string) $level,
                'tmp_dir' => $tmpDir,
                'exclude_paths' => $this->formatList($excludePaths, '            '),
            ]
        );
    }

    public function generateDomain(): string
    {
        $phpstan = $this->config->get('phpstan', []);
        $includes = $phpstan['includes'] ?? ['vendor/larastan/larastan/extension.neon', 'vendor/nesbot/carbon/extension.neon'];
        $paths = $phpstan['paths']['domain'] ?? ['app/Domain'];
        $level = $phpstan['levels']['domain'] ?? 8;
        $tmpDir = $phpstan['tmp_dirs']['domain'] ?? 'storage/framework/phpstan-domain';

        return $this->renderer->render(
            $this->stubPath('phpstan-domain.neon.stub'),
            [
                'includes' => $this->formatList($includes, '    '),
                'domain_paths' => $this->formatList($paths, '        '),
                'domain_level' => (string) $level,
                'tmp_dir' => $tmpDir,
                'exclude_paths' => $this->formatList(['storage/*'], '            '),
            ]
        );
    }

    public function generateApplication(): string
    {
        $phpstan = $this->config->get('phpstan', []);
        $includes = $phpstan['includes'] ?? ['vendor/larastan/larastan/extension.neon', 'vendor/nesbot/carbon/extension.neon'];
        $paths = $phpstan['paths']['application'] ?? ['app/Application'];
        $level = $phpstan['levels']['application'] ?? 7;
        $tmpDir = $phpstan['tmp_dirs']['application'] ?? 'storage/framework/phpstan-application';

        return $this->renderer->render(
            $this->stubPath('phpstan-application.neon.stub'),
            [
                'includes' => $this->formatList($includes, '    '),
                'application_paths' => $this->formatList($paths, '        '),
                'application_level' => (string) $level,
                'tmp_dir' => $tmpDir,
                'exclude_paths' => $this->formatList(['storage/*'], '            '),
            ]
        );
    }

    protected function formatList(array $items, string $indent): string
    {
        if (empty($items)) {
            return '';
        }

        return implode("\n", array_map(fn ($item) => "{$indent}- {$item}", $items)) . "\n";
    }

    protected function stubPath(string $stub): string
    {
        return dirname(__DIR__, 2) . '/stubs/' . $stub;
    }
}
