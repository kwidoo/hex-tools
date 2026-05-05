<?php

namespace Kwidoo\HexTools\Application\Analysis;

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Domain\Architecture\ArchitectureIssue;
use Kwidoo\HexTools\Domain\Architecture\ArchitectureModuleReport;
use Kwidoo\HexTools\Domain\Architecture\ArchitectureReport;
use Kwidoo\HexTools\Infrastructure\Filesystem\SourceFileScanner;
use Kwidoo\HexTools\Scanners\ModuleScanner;

class ArchitectureInspector
{
    public function __construct(
        protected HexToolsConfig $config,
        protected ModuleScanner $moduleScanner,
        protected SourceFileScanner $sourceScanner,
        protected BaselineRepository $baseline
    ) {}

    public function inspect(?string $module = null, bool $useBaseline = true): ArchitectureReport
    {
        $modules = $module ? [$module] : $this->config->modules();
        $baselinePath = $this->config->get('baseline.path', base_path('.hex/baseline/architecture.json'));
        $baselineHashes = $useBaseline ? $this->baseline->hashes($baselinePath) : [];
        $reports = [];
        $outgoingByModule = [];

        foreach ($modules as $moduleName) {
            $scanned = $this->moduleScanner->scan($moduleName);
            $files = $this->sourceScanner->filesForModule($moduleName);
            $fileLayers = $this->fileLayers($files, $moduleName);
            $layers = array_values(array_unique(array_merge(array_keys($scanned), array_values(array_filter($fileLayers)))));
            sort($layers);

            $issues = $this->detectIssues($moduleName, $files, $fileLayers, $layers);
            $issues = array_map(fn (ArchitectureIssue $issue) => $this->withBaseline($issue, $baselineHashes), $issues);
            $outgoing = $this->outgoingDependencies($moduleName, $files);
            $outgoingByModule[$moduleName] = $outgoing;

            $reports[$moduleName] = new ArchitectureModuleReport(
                $moduleName,
                $this->modulePath($moduleName),
                $layers,
                $issues,
                $this->suggestions($issues),
                $this->publicApiCandidates($files),
                [],
                $outgoing
            );
        }

        $reports = $this->withIncomingDependencies($reports, $outgoingByModule);

        return new ArchitectureReport(array_values($reports));
    }

    /** @param array<string> $files @return array<string, string|null> */
    protected function fileLayers(array $files, string $module): array
    {
        $layers = [];
        foreach ($files as $file) {
            $layers[$file] = $this->sourceScanner->layerForFile($file, $module);
        }

        return $layers;
    }

    /** @param array<string> $files @param array<string, string|null> $fileLayers @param array<string> $layers @return array<ArchitectureIssue> */
    protected function detectIssues(string $module, array $files, array $fileLayers, array $layers): array
    {
        $issues = [];

        foreach ($this->expectedLayers() as $expectedLayer) {
            if (!in_array($expectedLayer, $layers, true)) {
                $issues[] = new ArchitectureIssue('warn', 'missing_layer', "{$expectedLayer} layer was not detected", null, $module, $expectedLayer, $expectedLayer);
            }
        }

        if (!$this->moduleReadmeExists($module)) {
            $issues[] = new ArchitectureIssue('warn', 'missing_module_readme', "{$module} module README.md is missing", null, $module, null, $module);
        }

        if (!$this->moduleTestsExist($module)) {
            $issues[] = new ArchitectureIssue('warn', 'missing_module_tests', "{$module} module tests were not detected", null, $module, null, $module);
        }

        foreach ($files as $file) {
            $layer = $fileLayers[$file] ?? null;
            $relative = $this->sourceScanner->relativePath($file);
            $imports = $this->sourceScanner->imports($file);
            $contents = file_get_contents($file);

            if ($layer === null) {
                $issues[] = new ArchitectureIssue('warn', 'unclassified_file', "File could not be classified into a configured layer", $relative, $module, null, $relative);
                continue;
            }

            if (!in_array($layer, $this->knownLayers(), true)) {
                $issues[] = new ArchitectureIssue('warn', 'unknown_layer', "{$layer} is not a known architecture layer", $relative, $module, $layer, $layer);
            }

            foreach ($imports as $import) {
                foreach ($this->dependencyIssues($module, $layer, $import, $relative) as $issue) {
                    $issues[] = $issue;
                }
            }

            if ($layer === 'Application' && preg_match('/\bconfig\s*\(/', $contents)) {
                $issues[] = new ArchitectureIssue(
                    'warn',
                    'application_depends_on_config',
                    'Application code reads framework configuration directly',
                    $relative,
                    $module,
                    $layer,
                    'config'
                );
            }
        }

        return array_merge($issues, $this->missingContractImplementations($module, $files));
    }

    /** @return array<ArchitectureIssue> */
    protected function dependencyIssues(string $module, string $layer, string $import, string $file): array
    {
        $issues = [];

        if ($layer === 'Domain' && str_contains($import, '\\Infrastructure\\')) {
            $issues[] = new ArchitectureIssue('fail', 'domain_depends_on_infrastructure', "Domain imports {$import}", $file, $module, $layer, $import);
        }

        if ($layer === 'Domain' && (str_starts_with($import, 'Illuminate\\') || str_starts_with($import, 'Symfony\\'))) {
            $issues[] = new ArchitectureIssue('fail', 'domain_depends_on_framework', "Domain imports {$import}", $file, $module, $layer, $import);
        }

        if ($layer === 'Domain' && (str_contains($import, '\\Http\\') || str_starts_with($import, 'Illuminate\\Http'))) {
            $issues[] = new ArchitectureIssue('fail', 'domain_depends_on_http', "Domain imports {$import}", $file, $module, $layer, $import);
        }

        if ($layer === 'Application' && (str_contains($import, '\\Http\\') || str_starts_with($import, 'Illuminate\\Http'))) {
            $issues[] = new ArchitectureIssue('fail', 'application_depends_on_http', "Application imports {$import}", $file, $module, $layer, $import);
        }

        if ($layer === 'Application' && str_contains($import, '\\Config\\')) {
            $issues[] = new ArchitectureIssue('warn', 'application_depends_on_config', "Application imports {$import}", $file, $module, $layer, $import);
        }

        if ($layer === 'Infrastructure' && (str_contains($import, '\\Http\\') || str_starts_with($import, 'Illuminate\\Http'))) {
            $issues[] = new ArchitectureIssue('warn', 'infrastructure_depends_on_http', "Infrastructure imports {$import}", $file, $module, $layer, $import);
        }

        return $issues;
    }

    /** @param array<string> $files @return array<ArchitectureIssue> */
    protected function missingContractImplementations(string $module, array $files): array
    {
        $issues = [];
        $contents = '';
        foreach ($files as $file) {
            $contents .= "\n" . file_get_contents($file);
        }

        foreach ($files as $file) {
            $class = $this->sourceScanner->className($file);
            if ($class === null || !preg_match('/(?:Contract|Interface)$/', $class)) {
                continue;
            }

            $shortName = substr($class, strrpos($class, '\\') + 1);
            if (!preg_match('/implements\s+[^{;]*\b' . preg_quote($shortName, '/') . '\b/', $contents)) {
                $issues[] = new ArchitectureIssue(
                    'warn',
                    'missing_contract_implementation',
                    "{$shortName} has no implementation detected",
                    $this->sourceScanner->relativePath($file),
                    $module,
                    $this->sourceScanner->layerForFile($file, $module),
                    $class
                );
            }
        }

        return $issues;
    }

    /** @param array<string> $files @return array<string> */
    protected function publicApiCandidates(array $files): array
    {
        $candidates = [];
        foreach ($files as $file) {
            $class = $this->sourceScanner->className($file);
            if ($class && preg_match('/(UseCase|Action|Command|Query|Service)$/', $class)) {
                $candidates[] = $class;
            }
        }

        sort($candidates);

        return $candidates;
    }

    /** @param array<string> $files @return array<string> */
    protected function outgoingDependencies(string $module, array $files): array
    {
        $dependencies = [];
        foreach ($files as $file) {
            foreach ($this->sourceScanner->imports($file) as $import) {
                foreach ($this->config->modules() as $candidate) {
                    if ($candidate !== $module && preg_match('/\\\\' . preg_quote($candidate, '/') . '(\\\\|$)/', $import)) {
                        $dependencies[] = $candidate;
                    }
                }
            }
        }

        $dependencies = array_values(array_unique($dependencies));
        sort($dependencies);

        return $dependencies;
    }

    /** @param array<string, ArchitectureModuleReport> $reports @param array<string, array<string>> $outgoingByModule @return array<string, ArchitectureModuleReport> */
    protected function withIncomingDependencies(array $reports, array $outgoingByModule): array
    {
        foreach ($reports as $name => $report) {
            $incoming = [];
            foreach ($outgoingByModule as $source => $outgoing) {
                if ($source !== $name && in_array($name, $outgoing, true)) {
                    $incoming[] = $source;
                }
            }

            $reports[$name] = new ArchitectureModuleReport(
                $report->name,
                $report->path,
                $report->layers,
                $report->issues,
                $report->suggestions,
                $report->publicApiCandidates,
                $incoming,
                $report->outgoingDependencies
            );
        }

        return $reports;
    }

    /** @param array<ArchitectureIssue> $issues @return array<string> */
    protected function suggestions(array $issues): array
    {
        $map = [
            'domain_depends_on_infrastructure' => 'Move infrastructure-specific logic behind a contract implemented outside Domain.',
            'domain_depends_on_framework' => 'Keep Domain layer framework-independent.',
            'domain_depends_on_http' => 'Move HTTP request and response concerns to the Http layer.',
            'application_depends_on_http' => 'Pass simple command/query DTOs into Application instead of HTTP objects.',
            'application_depends_on_config' => 'Inject configuration values into Application services.',
            'infrastructure_depends_on_http' => 'Keep controller/request/resource code out of Infrastructure services.',
            'missing_module_readme' => 'Add README.md for the module.',
            'missing_module_tests' => 'Add focused tests for the module behavior.',
            'missing_contract_implementation' => 'Add or bind an implementation for the contract.',
            'unknown_layer' => 'Update hex-tools layer configuration or move the file into a known layer.',
            'unclassified_file' => 'Move the file into a configured module layer or update module collectors.',
            'missing_layer' => 'Create the missing layer when the module needs that responsibility.',
        ];

        $suggestions = [];
        foreach ($issues as $issue) {
            if (isset($map[$issue->code])) {
                $suggestions[] = $map[$issue->code];
            }
        }

        return array_values(array_unique($suggestions));
    }

    /** @param array<string, array> $baselineHashes */
    protected function withBaseline(ArchitectureIssue $issue, array $baselineHashes): ArchitectureIssue
    {
        if ($baselineHashes === []) {
            return $issue;
        }

        return $issue->withBaselineStatus(isset($baselineHashes[$issue->stableHash()]) ? 'existing' : 'new');
    }

    protected function modulePath(string $module): string
    {
        $appPath = $this->config->get('paths.app', base_path('app'));
        foreach ([
            $appPath . '/Modules/' . $module,
            $appPath . '/Domain/' . $module,
            $appPath . '/Application/' . $module,
        ] as $path) {
            if (is_dir($path)) {
                return $this->sourceScanner->relativePath($path);
            }
        }

        return $this->sourceScanner->relativePath($appPath);
    }

    protected function moduleReadmeExists(string $module): bool
    {
        $appPath = $this->config->get('paths.app', base_path('app'));
        foreach ([
            $appPath . '/Modules/' . $module . '/README.md',
            $appPath . '/Domain/' . $module . '/README.md',
            $this->config->get('paths.docs', base_path('docs/architecture')) . '/modules/' . strtolower($module) . '.md',
        ] as $path) {
            if (is_file($path)) {
                return true;
            }
        }

        return false;
    }

    protected function moduleTestsExist(string $module): bool
    {
        $testsPath = base_path('tests');
        if (!is_dir($testsPath)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($testsPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || !in_array($file->getExtension(), ['php', 'pest'], true)) {
                continue;
            }

            if (str_contains($file->getBasename(), $module) || str_contains(file_get_contents($file->getPathname()), $module)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string> */
    protected function expectedLayers(): array
    {
        return $this->config->get('architecture.expected_layers', ['Domain', 'Application']);
    }

    /** @return array<string> */
    protected function knownLayers(): array
    {
        return array_values(array_unique(array_merge(
            ['Domain', 'Application', 'Infrastructure', 'Http', 'Models', 'Support'],
            $this->expectedLayers()
        )));
    }
}
