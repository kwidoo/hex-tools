<?php

namespace Kwidoo\HexTools\Generators;

use Kwidoo\HexTools\Config\HexToolsConfig;
use Symfony\Component\Yaml\Yaml;

class DeptracModulesGenerator
{
    public function __construct(protected HexToolsConfig $config) {}

    public function generate(): string
    {
        $modules = $this->config->modules();
        $collectorPatterns = $this->config->moduleCollectors();
        $moduleRules = $this->config->moduleRules();

        $deptrac = [
            'deptrac' => [
                'paths' => ['app'],
                'layers' => $this->buildModuleLayers($modules, $collectorPatterns),
                'ruleset' => $this->buildModuleRuleset($moduleRules, $modules),
            ],
        ];

        return Yaml::dump($deptrac, 6, 2);
    }

    protected function buildModuleLayers(array $modules, array $collectorPatterns): array
    {
        $result = [];

        foreach ($modules as $module) {
            $collectors = [];

            foreach ($collectorPatterns as $pattern) {
                $resolved = str_replace('{module}', $module, $pattern);

                if (str_starts_with($resolved, 'app/')) {
                    $collectors[] = ['type' => 'directory', 'value' => $resolved];
                } else {
                    $collectors[] = ['type' => 'classLike', 'value' => $resolved];
                }
            }

            $result[] = ['name' => $module, 'collectors' => $collectors];
        }

        return $result;
    }

    protected function buildModuleRuleset(array $moduleRules, array $modules): array
    {
        $ruleset = [];

        foreach ($modules as $module) {
            $allowed = $moduleRules[$module] ?? [$module];
            $dependencies = array_values(array_filter(
                $allowed,
                fn (string $dependency): bool => $dependency !== $module
            ));
            $ruleset[$module] = !empty($dependencies) ? $dependencies : null;
        }

        return $ruleset;
    }
}
