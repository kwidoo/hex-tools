<?php

namespace Kwidoo\HexTools\Generators;

use Kwidoo\HexTools\Config\HexToolsConfig;
use Symfony\Component\Yaml\Yaml;

class DeptracLayersGenerator
{
    public function __construct(protected HexToolsConfig $config) {}

    public function generate(): string
    {
        $layers = $this->config->layers();

        $deptrac = [
            'deptrac' => [
                'paths' => ['app'],
                'layers' => $this->buildLayers($layers),
                'ruleset' => $this->buildRuleset(),
            ],
        ];

        return Yaml::dump($deptrac, 6, 2);
    }

    protected function buildLayers(array $layers): array
    {
        $result = [];

        foreach ($layers as $name => $path) {
            $result[] = [
                'name' => ucfirst($name),
                'collectors' => [
                    ['type' => 'directory', 'value' => $path . '/.*'],
                ],
            ];
        }

        $result[] = [
            'name' => 'Framework',
            'collectors' => [
                ['type' => 'classLike', 'value' => 'Illuminate\\\\.*'],
                ['type' => 'classLike', 'value' => 'Laravel\\\\.*'],
            ],
        ];

        $result[] = [
            'name' => 'Vendor',
            'collectors' => [
                ['type' => 'directory', 'value' => 'vendor/.*'],
            ],
        ];

        return $result;
    }

    protected function buildRuleset(): array
    {
        return [
            'Domain' => null,
            'Application' => ['Domain'],
            'Http' => ['Application', 'Domain', 'Framework', 'Vendor'],
            'Infrastructure' => ['Application', 'Domain', 'Models', 'Framework', 'Vendor'],
            'Models' => ['Domain', 'Framework', 'Vendor'],
            'Providers' => ['Domain', 'Application', 'Http', 'Infrastructure', 'Models', 'Support', 'Console', 'Framework', 'Vendor'],
            'Support' => ['Framework', 'Vendor'],
            'Console' => ['Application', 'Domain', 'Framework', 'Vendor'],
        ];
    }
}
