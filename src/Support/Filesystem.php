<?php

namespace Kwidoo\HexTools\Support;

class Filesystem
{
    public function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    public function put(string $path, string $contents): void
    {
        file_put_contents($path, $contents);
    }

    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    public function get(string $path): string
    {
        return file_get_contents($path);
    }

    public function files(string $directory, string $extension = 'php'): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === $extension) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
