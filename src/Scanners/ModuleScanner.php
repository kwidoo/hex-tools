<?php

namespace Kwidoo\HexTools\Scanners;

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Support\Filesystem;

class ModuleScanner
{
    public function __construct(
        protected HexToolsConfig $config,
        protected Filesystem $filesystem,
        protected ClassNameResolver $resolver
    ) {}

    public function scan(string $module): array
    {
        $appPath = $this->config->get('paths.app');

        $result = [
            'Domain' => $this->scanDirectory($appPath . '/Domain/' . $module),
            'Application' => $this->scanDirectory($appPath . '/Application/' . $module),
            'Http' => $this->scanHttpLayer($appPath, $module),
            'Infrastructure' => $this->scanDirectory($appPath . '/Infrastructure/' . $module),
            'Models' => $this->scanModels($appPath . '/Models', $module),
            'Support' => $this->scanDirectory($appPath . '/Support/' . $module),
        ];

        return array_filter($result, fn ($classes) => !empty($classes));
    }

    protected function scanDirectory(string $path): array
    {
        $files = $this->filesystem->files($path);

        return array_map(fn ($f) => $this->resolver->resolve($f), $files);
    }

    protected function scanHttpLayer(string $appPath, string $module): array
    {
        $classes = [];
        $dirs = [
            $appPath . '/Http/Controllers',
            $appPath . '/Http/Requests',
            $appPath . '/Http/Resources',
        ];

        foreach ($dirs as $dir) {
            foreach ($this->filesystem->files($dir) as $file) {
                $basename = basename($file, '.php');
                if (str_contains($basename, $module)) {
                    $classes[] = $this->resolver->resolve($file);
                }
            }
        }

        return $classes;
    }

    protected function scanModels(string $modelsPath, string $module): array
    {
        $classes = [];

        foreach ($this->filesystem->files($modelsPath) as $file) {
            $basename = basename($file, '.php');
            if ($basename === $module || str_starts_with($basename, $module)) {
                $classes[] = $this->resolver->resolve($file);
            }
        }

        return $classes;
    }
}
