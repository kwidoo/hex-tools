<?php

namespace Kwidoo\HexTools\Generators;

class ComposerScriptsInstaller
{
    protected array $scripts = [
        'hex:layers' => 'vendor/bin/deptrac analyse --config-file=deptrac.layers.yaml',
        'hex:modules' => 'vendor/bin/deptrac analyse --config-file=deptrac.modules.yaml',
        'hex:unassigned:layers' => 'vendor/bin/deptrac debug:unassigned --config-file=deptrac.layers.yaml',
        'hex:unassigned:modules' => 'vendor/bin/deptrac debug:unassigned --config-file=deptrac.modules.yaml',
        'hex:graph:layers' => 'vendor/bin/deptrac analyse --config-file=deptrac.layers.yaml --formatter=mermaidjs --output=build/architecture/layers.mmd',
        'hex:graph:modules' => 'vendor/bin/deptrac analyse --config-file=deptrac.modules.yaml --formatter=mermaidjs --output=build/architecture/modules.mmd',
    ];

    public function install(string $composerJsonPath): bool
    {
        if (!file_exists($composerJsonPath)) {
            return false;
        }

        $composer = json_decode(file_get_contents($composerJsonPath), true);

        if (!isset($composer['scripts'])) {
            $composer['scripts'] = [];
        }

        $added = 0;
        foreach ($this->scripts as $key => $command) {
            if (!isset($composer['scripts'][$key])) {
                $composer['scripts'][$key] = $command;
                $added++;
            }
        }

        file_put_contents(
            $composerJsonPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );

        return true;
    }

    public function getScripts(): array
    {
        return $this->scripts;
    }
}
