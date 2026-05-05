<?php

namespace Kwidoo\HexTools\Support;

final class ToolAvailability
{
    public function hasPhpStan(): bool
    {
        return file_exists(base_path('vendor/bin/phpstan'));
    }

    public function hasDeptrac(): bool
    {
        return file_exists(base_path('vendor/bin/deptrac'));
    }

    public function hasPhpMd(): bool
    {
        return file_exists(base_path('vendor/bin/phpmd'));
    }

    public function hasPint(): bool
    {
        return file_exists(base_path('vendor/bin/pint'));
    }

    public function hasRector(): bool
    {
        return file_exists(base_path('vendor/bin/rector'));
    }
}
