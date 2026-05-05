<?php

namespace Kwidoo\HexTools\Reports;

final class ConfigStatus
{
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly bool $exists,
    ) {}
}
