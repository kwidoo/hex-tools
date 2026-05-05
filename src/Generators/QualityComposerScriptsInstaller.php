<?php

namespace Kwidoo\HexTools\Generators;

class QualityComposerScriptsInstaller extends ComposerScriptsInstaller
{
    protected array $scripts = [];

    protected array $basicScripts = [
        'hex:doctor' => 'php artisan hex:doctor',
        'hex:layers' => 'vendor/bin/deptrac analyse --config-file=deptrac.layers.yaml',
        'hex:modules' => 'vendor/bin/deptrac analyse --config-file=deptrac.modules.yaml',
        'stan' => 'vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=1G',
        'stan:domain' => 'vendor/bin/phpstan analyse --configuration=phpstan-domain.neon --memory-limit=1G',
        'stan:application' => 'vendor/bin/phpstan analyse --configuration=phpstan-application.neon --memory-limit=1G',
        'fmt' => 'vendor/bin/pint',
        'fmt:test' => 'vendor/bin/pint --test',
    ];

    protected array $strictScripts = [
        'md' => 'vendor/bin/phpmd app text phpmd.xml',
        'md:domain' => 'vendor/bin/phpmd app/Domain text phpmd.xml',
        'md:application' => 'vendor/bin/phpmd app/Application text phpmd.xml',
        'rector' => 'vendor/bin/rector process',
        'rector:dry' => 'vendor/bin/rector process --dry-run',
    ];

    protected array $ciScripts = [
        'quality' => ['@fmt:test', '@stan', '@hex:layers', '@hex:modules', '@md', '@rector:dry'],
        'quality:soft' => ['@fmt:test', '@stan', '@hex:layers', '@hex:modules'],
        'security:audit' => 'composer audit',
        'security:audit:locked' => 'composer audit --locked',
    ];

    public function setProfile(string $profile): void
    {
        $this->scripts = match ($profile) {
            'strict' => array_merge($this->basicScripts, $this->strictScripts),
            'ci' => array_merge($this->basicScripts, $this->strictScripts, $this->ciScripts),
            default => $this->basicScripts,
        };
    }
}
