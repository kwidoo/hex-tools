<?php

namespace Kwidoo\HexTools\Generators;

class PintComposerScriptsInstaller extends ComposerScriptsInstaller
{
    protected array $scripts = [
        'fmt' => 'vendor/bin/pint',
        'fmt:test' => 'vendor/bin/pint --test',
    ];
}
