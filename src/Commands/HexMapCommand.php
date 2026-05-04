<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Scanners\ModuleScanner;
use Kwidoo\HexTools\Support\Filesystem;

class HexMapCommand extends Command
{
    protected $signature = 'hex:map
        {module : Module name, e.g. Product}
        {--format=table : Output format: table|markdown|json}
        {--output= : Optional output file path}';

    protected $description = 'Show architecture map for a module.';

    public function __construct(
        protected HexToolsConfig $config,
        protected ModuleScanner $scanner,
        protected Filesystem $filesystem
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $module = $this->argument('module');
        $format = $this->option('format');
        $outputFile = $this->option('output');

        $classes = $this->scanner->scan($module);
        $rules = $this->config->moduleRules();
        $allowed = $rules[$module] ?? [$module];

        $content = match ($format) {
            'json' => $this->formatJson($module, $classes, $allowed),
            'markdown' => $this->formatMarkdown($module, $classes, $allowed),
            default => $this->formatTable($module, $classes, $allowed),
        };

        if ($outputFile) {
            $this->filesystem->put(base_path($outputFile), $content);
            $this->info("Saved to: {$outputFile}");
        } else {
            foreach (explode("\n", rtrim($content)) as $line) {
                $this->line($line);
            }
        }

        return self::SUCCESS;
    }

    protected function formatTable(string $module, array $classes, array $allowed): string
    {
        $lines = ["{$module} Module", ''];

        foreach ($classes as $layer => $layerClasses) {
            $lines[] = "{$layer}:";
            foreach ($layerClasses as $class) {
                $lines[] = "  {$class}";
            }
            $lines[] = '';
        }

        $lines[] = 'Allowed module dependencies:';
        foreach ($allowed as $dep) {
            $lines[] = "  {$dep}";
        }
        $lines[] = '';
        $lines[] = 'Useful commands:';
        $lines[] = "  vendor/bin/deptrac debug:layer --config-file=deptrac.modules.yaml {$module}";

        $others = array_values(array_diff($this->config->modules(), [$module]));
        if (!empty($others)) {
            $lines[] = "  vendor/bin/deptrac debug:dependencies --config-file=deptrac.modules.yaml {$module} {$others[0]}";
        }

        return implode("\n", $lines) . "\n";
    }

    protected function formatMarkdown(string $module, array $classes, array $allowed): string
    {
        $lines = ["# {$module} Module", ''];

        foreach ($classes as $layer => $layerClasses) {
            $lines[] = "## {$layer}";
            foreach ($layerClasses as $class) {
                $lines[] = "- `{$class}`";
            }
            $lines[] = '';
        }

        $lines[] = '## Allowed Dependencies';
        foreach ($allowed as $dep) {
            $lines[] = "- {$dep}";
        }

        return implode("\n", $lines) . "\n";
    }

    protected function formatJson(string $module, array $classes, array $allowed): string
    {
        return json_encode([
            'module' => $module,
            'classes' => $classes,
            'allowed_dependencies' => $allowed,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
}
