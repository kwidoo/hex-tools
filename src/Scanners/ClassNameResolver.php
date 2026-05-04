<?php

namespace Kwidoo\HexTools\Scanners;

class ClassNameResolver
{
    public function __construct(
        protected string $appPath,
        protected string $namespace
    ) {}

    public function resolve(string $filePath): string
    {
        $relative = str_replace([$this->appPath . '/', $this->appPath . DIRECTORY_SEPARATOR], '', $filePath);
        $relative = str_replace('.php', '', $relative);
        $parts = str_replace(['/', DIRECTORY_SEPARATOR], '\\', $relative);

        return $this->namespace . '\\' . $parts;
    }
}
