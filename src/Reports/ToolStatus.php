<?php

namespace Kwidoo\HexTools\Reports;

final class ToolStatus
{
    public function __construct(
        public readonly string $name,
        public readonly bool $installed,
        public readonly string $binary,
    ) {}
}
