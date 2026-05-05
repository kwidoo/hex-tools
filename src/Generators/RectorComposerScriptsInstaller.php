<?php

namespace Kwidoo\HexTools\Generators;

class RectorComposerScriptsInstaller extends ComposerScriptsInstaller
{
    protected array $scripts = [
        'rector' => 'vendor/bin/rector process',
        'rector:dry' => 'vendor/bin/rector process --dry-run',
    ];
}
