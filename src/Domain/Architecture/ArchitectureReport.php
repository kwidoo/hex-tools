<?php

namespace Kwidoo\HexTools\Domain\Architecture;

class ArchitectureReport
{
    /** @param array<ArchitectureModuleReport> $modules */
    public function __construct(public readonly array $modules) {}

    /** @return array<ArchitectureIssue> */
    public function issues(): array
    {
        if ($this->modules === []) {
            return [];
        }

        return array_values(array_merge(...array_map(
            fn (ArchitectureModuleReport $module) => $module->issues,
            $this->modules
        ))) ?: [];
    }

    public function hasNewFailures(): bool
    {
        foreach ($this->issues() as $issue) {
            if ($issue->severity === 'fail' && $issue->baselineStatus !== 'existing') {
                return true;
            }
        }

        return false;
    }

    public function summary(): array
    {
        $issues = $this->issues();
        $failures = count(array_filter($issues, fn (ArchitectureIssue $issue) => $issue->severity === 'fail'));
        $warnings = count(array_filter($issues, fn (ArchitectureIssue $issue) => $issue->severity === 'warn'));
        $new = count(array_filter($issues, fn (ArchitectureIssue $issue) => $issue->baselineStatus === 'new'));
        $existing = count(array_filter($issues, fn (ArchitectureIssue $issue) => $issue->baselineStatus === 'existing'));

        return [
            'modules' => count($this->modules),
            'issues' => count($issues),
            'failures' => $failures,
            'warnings' => $warnings,
            'new_issues' => $new,
            'existing_baseline_issues' => $existing,
            'score' => max(0, 100 - ($failures * 12) - ($warnings * 4)),
        ];
    }

    public function toArray(): array
    {
        return [
            'modules' => array_map(fn (ArchitectureModuleReport $module) => $module->toArray(), $this->modules),
            'summary' => $this->summary(),
        ];
    }
}
