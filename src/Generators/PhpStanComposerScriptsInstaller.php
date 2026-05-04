<?php

namespace Kwidoo\HexTools\Generators;

class PhpStanComposerScriptsInstaller extends ComposerScriptsInstaller
{
    protected array $scripts = [
        'stan' => 'vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=1G',
        'stan:domain' => 'vendor/bin/phpstan analyse --configuration=phpstan-domain.neon --memory-limit=1G',
        'stan:application' => 'vendor/bin/phpstan analyse --configuration=phpstan-application.neon --memory-limit=1G',
        'stan:baseline' => 'vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --generate-baseline --memory-limit=1G',
    ];
}
