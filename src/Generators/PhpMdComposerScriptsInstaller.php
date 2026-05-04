<?php

namespace Kwidoo\HexTools\Generators;

class PhpMdComposerScriptsInstaller extends ComposerScriptsInstaller
{
    protected array $scripts = [
        'md' => 'vendor/bin/phpmd app text phpmd.xml',
        'md:baseline' => 'vendor/bin/phpmd app text phpmd.xml --generate-baseline --baseline-file phpmd.baseline.xml',
        'md:update-baseline' => 'vendor/bin/phpmd app text phpmd.xml --update-baseline --baseline-file phpmd.baseline.xml',
        'md:domain' => 'vendor/bin/phpmd app/Domain text phpmd.xml',
        'md:application' => 'vendor/bin/phpmd app/Application text phpmd.xml',
        'md:report' => 'vendor/bin/phpmd app html phpmd.xml --reportfile build/architecture/phpmd.html',
    ];
}
