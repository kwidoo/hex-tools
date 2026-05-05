<?php

namespace Kwidoo\HexTools\Application\Analysis;

use Kwidoo\HexTools\Domain\Architecture\ArchitectureIssue;
use Kwidoo\HexTools\Domain\Architecture\ArchitectureModuleReport;
use Kwidoo\HexTools\Domain\Architecture\ArchitectureReport;
use Kwidoo\HexTools\Support\Filesystem;
use Kwidoo\HexTools\Support\Str;

class ArchitectureDocumentationGenerator
{
    public function __construct(
        protected ArchitectureInspector $inspector,
        protected Filesystem $filesystem
    ) {}

    /** @return array<string> */
    public function generate(string $outputPath, ?string $module = null, bool $force = false): array
    {
        $report = $this->inspector->inspect($module);
        $this->filesystem->ensureDirectory($outputPath);
        $this->filesystem->ensureDirectory($outputPath . '/modules');

        if ($module !== null) {
            return $this->writeModuleDocs($outputPath, $report, $force);
        }

        return array_merge(
            $this->writeOverviewDocs($outputPath, $report, $force),
            $this->writeModuleDocs($outputPath, $report, $force)
        );
    }

    /** @return array<string> */
    protected function writeOverviewDocs(string $outputPath, ArchitectureReport $report, bool $force): array
    {
        $files = [
            $outputPath . '/index.md' => $this->index($report),
            $outputPath . '/modules.md' => $this->modules($report),
            $outputPath . '/layers.md' => $this->layers($report),
            $outputPath . '/dependencies.md' => $this->dependencies($report),
            $outputPath . '/violations.md' => $this->violations($report),
            $outputPath . '/baseline.md' => $this->baseline($report),
        ];

        return $this->writeFiles($files, $force);
    }

    /** @return array<string> */
    protected function writeModuleDocs(string $outputPath, ArchitectureReport $report, bool $force): array
    {
        $files = [];
        foreach ($report->modules as $module) {
            $files[$outputPath . '/modules/' . Str::kebab($module->name) . '.md'] = $this->module($module);
        }

        return $this->writeFiles($files, $force);
    }

    /** @param array<string, string> $files @return array<string> */
    protected function writeFiles(array $files, bool $force): array
    {
        $written = [];
        foreach ($files as $path => $content) {
            if (is_file($path) && !$force) {
                continue;
            }

            $this->filesystem->put($path, $content);
            $written[] = $path;
        }

        return $written;
    }

    protected function index(ArchitectureReport $report): string
    {
        $summary = $report->summary();

        return "# Architecture\n\n"
            . "- Modules: {$summary['modules']}\n"
            . "- Issues: {$summary['issues']}\n"
            . "- Score: {$summary['score']}\n\n"
            . "## Documents\n\n"
            . "- [Modules](modules.md)\n"
            . "- [Layers](layers.md)\n"
            . "- [Dependencies](dependencies.md)\n"
            . "- [Violations](violations.md)\n"
            . "- [Baseline](baseline.md)\n";
    }

    protected function modules(ArchitectureReport $report): string
    {
        $lines = ["# Modules", ''];
        foreach ($report->modules as $module) {
            $lines[] = "- [{$module->name}](modules/" . Str::kebab($module->name) . '.md)';
        }

        return implode("\n", $lines) . "\n";
    }

    protected function layers(ArchitectureReport $report): string
    {
        $lines = ["# Layers", ''];
        foreach ($report->modules as $module) {
            $lines[] = "## {$module->name}";
            foreach ($module->layers as $layer) {
                $lines[] = "- {$layer}";
            }
            $lines[] = '';
        }

        return implode("\n", $lines) . "\n";
    }

    protected function dependencies(ArchitectureReport $report): string
    {
        $lines = ["# Dependencies", '', '| Module | Incoming | Outgoing |', '|---|---|---|'];
        foreach ($report->modules as $module) {
            $lines[] = '| ' . $module->name . ' | ' . implode(', ', $module->incomingDependencies) . ' | ' . implode(', ', $module->outgoingDependencies) . ' |';
        }

        return implode("\n", $lines) . "\n";
    }

    protected function violations(ArchitectureReport $report): string
    {
        $lines = ["# Architecture Issues", '', '| Module | Severity | Code | Message | File |', '|---|---|---|---|---|'];
        foreach ($report->modules as $module) {
            foreach ($module->issues as $issue) {
                $lines[] = '| ' . $module->name . ' | ' . $issue->severity . ' | ' . $issue->code . ' | ' . $issue->message . ' | ' . ($issue->file ?? '') . ' |';
            }
        }

        return implode("\n", $lines) . "\n";
    }

    protected function baseline(ArchitectureReport $report): string
    {
        $lines = ["# Baseline", '', '| Hash | Code | File |', '|---|---|---|'];
        foreach ($report->issues() as $issue) {
            $lines[] = '| ' . $issue->stableHash() . ' | ' . $issue->code . ' | ' . ($issue->file ?? '') . ' |';
        }

        return implode("\n", $lines) . "\n";
    }

    protected function module(ArchitectureModuleReport $module): string
    {
        $lines = ["# {$module->name} Module", '', '## Layers', ''];
        foreach ($module->layers as $layer) {
            $lines[] = "- {$layer}";
        }

        $lines[] = '';
        $lines[] = '## Public API Candidates';
        $lines[] = '';
        foreach ($module->publicApiCandidates as $candidate) {
            $lines[] = "- `{$candidate}`";
        }

        $lines[] = '';
        $lines[] = '## Incoming Dependencies';
        $lines[] = '';
        foreach ($module->incomingDependencies as $dependency) {
            $lines[] = "- {$dependency}";
        }

        $lines[] = '';
        $lines[] = '## Outgoing Dependencies';
        $lines[] = '';
        foreach ($module->outgoingDependencies as $dependency) {
            $lines[] = "- {$dependency}";
        }

        $lines[] = '';
        $lines[] = '## Architecture Issues';
        $lines[] = '';
        $lines[] = '| Severity | Code | Message |';
        $lines[] = '|---|---|---|';
        foreach ($module->issues as $issue) {
            $lines[] = '| ' . $issue->severity . ' | ' . $issue->code . ' | ' . $issue->message . ' |';
        }

        $lines[] = '';
        $lines[] = '## Suggestions';
        $lines[] = '';
        foreach ($module->suggestions as $suggestion) {
            $lines[] = "- {$suggestion}";
        }

        return implode("\n", $lines) . "\n";
    }
}
