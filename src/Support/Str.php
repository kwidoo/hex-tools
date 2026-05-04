<?php

namespace Kwidoo\HexTools\Support;

class Str
{
    public static function kebab(string $value): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $value));
    }

    public static function slug(string $value): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $value), '-'));
    }
}
