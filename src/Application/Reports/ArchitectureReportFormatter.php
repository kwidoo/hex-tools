<?php

namespace Kwidoo\HexTools\Application\Reports;

use Kwidoo\HexTools\Domain\Architecture\ArchitectureIssue;
use Kwidoo\HexTools\Domain\Architecture\ArchitectureModuleReport;
use Kwidoo\HexTools\Domain\Architecture\ArchitectureReport;
use Kwidoo\HexTools\Domain\Architecture\DoctorReport;
use Kwidoo\HexTools\Domain\Architecture\RuleExplanation;

class ArchitectureReportFormatter
{
    public function architecture(ArchitectureReport $report, string $format): string
    {
        return match ($this->normalize($format)) {
            'json' => json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            'md' => $this->architectureMarkdown($report),
            default => $this->architectureConsole($report),
        };
    }

    public function doctor(DoctorReport $report, string $format): string
    {
        return match ($this->normalize($format)) {
            'json' => json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            'md' => $this->doctorMarkdown($report),
            default => $this->doctorConsole($report),
        };
    }

    public function explanation(RuleExplanation $explanation): string
    {
        return "Rule: {$explanation->code}\n\n"
            . "Why this matters:\n{$explanation->why}\n\n"
            . "Bad example:\n{$explanation->badExample}\n\n"
            . "Better approach:\n{$explanation->betterApproach}\n\n"
            . "Suggested structure:\n{$explanation->suggestedStructure}\n";
    }

    protected function architectureConsole(ArchitectureReport $report): string
    {
        $lines = ['Hex Architecture Inspection', ''];
        foreach ($report->modules as $module) {
            $lines = array_merge($lines, $this->moduleConsole($module));
        }

        $summary = $report->summary();
        $lines[] = 'Summary:';
        $lines[] = "  Modules: {$summary['modules']}";
        $lines[] = "  Issues: {$summary['issues']}";
        $lines[] = "  Warnings: {$summary['warnings']}";
        $lines[] = "  Score: {$summary['score']}";

        return implode("\n", $lines) . "\n";
    }

    /** @return array<string> */
    protected function moduleConsole(ArchitectureModuleReport $module): array
    {
        $lines = ["Module: {$module->name}", '', 'Detected layers:'];
        foreach ($module->layers as $layer) {
            $lines[] = "  [OK] {$layer}";
        }

        $lines[] = '';
        $lines[] = 'Issues:';
        if ($module->issues === []) {
            $lines[] = '  [OK] No architecture issues detected';
        }

        foreach ($module->issues as $issue) {
            $status = strtoupper($issue->severity);
            $baseline = $issue->baselineStatus ? " ({$issue->baselineStatus})" : '';
            $lines[] = "  [{$status}] {$issue->message} [{$issue->code}]{$baseline}";
            if ($issue->file) {
                $lines[] = "        {$issue->file}";
            }
        }

        $lines[] = '';
        $lines[] = 'Suggestions:';
        foreach ($module->suggestions as $suggestion) {
            $lines[] = "  - {$suggestion}";
        }
        if ($module->suggestions === []) {
            $lines[] = '  - No suggestions';
        }
        $lines[] = '';

        return $lines;
    }

    protected function architectureMarkdown(ArchitectureReport $report): string
    {
        $lines = ['# Hex Architecture Inspection', ''];
        foreach ($report->modules as $module) {
            $lines[] = "## {$module->name}";
            $lines[] = '';
            $lines[] = '### Layers';
            foreach ($module->layers as $layer) {
                $lines[] = "- {$layer}";
            }
            $lines[] = '';
            $lines[] = '### Issues';
            $lines[] = '| Severity | Code | Message | File |';
            $lines[] = '|---|---|---|---|';
            foreach ($module->issues as $issue) {
                $lines[] = '| ' . $issue->severity . ' | ' . $issue->code . ' | ' . $issue->message . ' | ' . ($issue->file ?? '') . ' |';
            }
            $lines[] = '';
        }

        return implode("\n", $lines) . "\n";
    }

    protected function doctorConsole(DoctorReport $report): string
    {
        $lines = ['Hex Tools Doctor', ''];
        foreach ($report->checks as $check) {
            $lines[] = '[' . strtoupper($check->status) . '] ' . $check->message;
        }

        return implode("\n", $lines) . "\n";
    }

    protected function doctorMarkdown(DoctorReport $report): string
    {
        $lines = ['# Hex Tools Doctor', '', '| Status | Code | Message |', '|---|---|---|'];
        foreach ($report->checks as $check) {
            $lines[] = '| ' . $check->status . ' | ' . $check->code . ' | ' . $check->message . ' |';
        }

        return implode("\n", $lines) . "\n";
    }

    protected function normalize(string $format): string
    {
        return match ($format) {
            'markdown' => 'md',
            'table', 'console' => 'table',
            default => $format,
        };
    }
}
