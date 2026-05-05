<?php

namespace Kwidoo\HexTools\Infrastructure\Filesystem;

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Support\Filesystem;

class SourceFileScanner
{
    public function __construct(
        protected HexToolsConfig $config,
        protected Filesystem $filesystem
    ) {}

    /** @return array<string> */
    public function phpFiles(): array
    {
        return $this->filesystem->files($this->config->get('paths.app', base_path('app')));
    }

    /** @return array<string> */
    public function filesForModule(string $module): array
    {
        $files = [];
        foreach ($this->phpFiles() as $file) {
            if ($this->looksLikeModuleFile($file, $module)) {
                $files[] = $file;
            }
        }

        return array_values(array_unique($files));
    }

    public function relativePath(string $path): string
    {
        $base = rtrim(base_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return str_starts_with($path, $base)
            ? str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($base)))
            : str_replace(DIRECTORY_SEPARATOR, '/', $path);
    }

    /** @return array<string> */
    public function imports(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        preg_match_all('/^use\s+([^;]+);/m', file_get_contents($file), $matches);

        return array_values(array_filter(array_map(function (string $import): string {
            $import = trim($import);
            $import = preg_replace('/\s+as\s+.+$/i', '', $import) ?? $import;

            return ltrim($import, '\\');
        }, $matches[1] ?? [])));
    }

    public function className(string $file): ?string
    {
        if (!is_file($file)) {
            return null;
        }

        $contents = file_get_contents($file);
        preg_match('/^namespace\s+([^;]+);/m', $contents, $namespace);
        preg_match('/\b(?:class|interface|trait|enum)\s+([A-Za-z0-9_]+)/', $contents, $class);

        if (empty($class[1])) {
            return null;
        }

        return empty($namespace[1])
            ? $class[1]
            : trim($namespace[1]) . '\\' . $class[1];
    }

    public function layerForFile(string $file, string $module): ?string
    {
        $relative = $this->relativePath($file);
        $appRelative = $this->appRelativePath($file);
        $layerPaths = $this->configuredLayerPaths();

        foreach ($layerPaths as $layer => $path) {
            $normalized = trim(str_replace('\\', '/', $path), '/');
            if (str_starts_with($normalized, 'app/')) {
                $normalized = substr($normalized, 4);
            }

            if (
                str_contains($relative, $normalized . '/' . $module . '/')
                || str_contains($appRelative, $normalized . '/' . $module . '/')
                || str_contains($relative, $normalized . '/' . $module . '.php')
                || str_contains($appRelative, $normalized . '/' . $module . '.php')
                || ($layer === 'Http' && str_starts_with($appRelative, $normalized . '/') && str_contains(basename($file), $module))
                || ($layer === 'Models' && str_starts_with($appRelative, $normalized . '/') && str_starts_with(basename($file, '.php'), $module))
            ) {
                return $layer;
            }
        }

        if (str_contains($relative, '/Modules/' . $module . '/') || str_starts_with($appRelative, 'Modules/' . $module . '/')) {
            foreach (['Domain', 'Application', 'Infrastructure', 'Http', 'Support'] as $layer) {
                if (str_contains($relative, '/Modules/' . $module . '/' . $layer . '/') || str_contains($appRelative, 'Modules/' . $module . '/' . $layer . '/')) {
                    return $layer;
                }
            }
        }

        return null;
    }

    /** @return array<string, string> */
    protected function configuredLayerPaths(): array
    {
        $paths = [];
        foreach ($this->config->layers() as $key => $value) {
            if (is_string($value)) {
                $paths[$this->displayLayer($key)] = $value;
            } elseif (is_array($value) && isset($value['paths'][0])) {
                $paths[$value['name'] ?? $this->displayLayer($key)] = $value['paths'][0];
            }
        }

        return $paths;
    }

    protected function displayLayer(string $layer): string
    {
        return ucfirst(str_replace(['-', '_'], ' ', $layer));
    }

    protected function appRelativePath(string $path): string
    {
        $appPath = rtrim($this->config->get('paths.app', base_path('app')), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return str_starts_with($path, $appPath)
            ? str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($appPath)))
            : str_replace(DIRECTORY_SEPARATOR, '/', $path);
    }

    protected function looksLikeModuleFile(string $file, string $module): bool
    {
        $relative = $this->relativePath($file);

        return str_contains($relative, '/' . $module . '/')
            || str_contains($relative, '/Modules/' . $module . '/')
            || str_contains(basename($file, '.php'), $module);
    }
}
