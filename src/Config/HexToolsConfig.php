<?php

namespace Kwidoo\HexTools\Config;

class HexToolsConfig
{
    public function __construct(protected array $config) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function modules(): array
    {
        return $this->config['modules'] ?? [];
    }

    public function layers(): array
    {
        return $this->config['layers'] ?? [];
    }

    public function moduleRules(): array
    {
        return $this->config['module_rules'] ?? [];
    }

    public function moduleCollectors(): array
    {
        return $this->config['module_collectors'] ?? [];
    }

    public function paths(): array
    {
        return $this->config['paths'] ?? [];
    }

    public function namespace(): string
    {
        return $this->config['namespace'] ?? 'App';
    }
}
