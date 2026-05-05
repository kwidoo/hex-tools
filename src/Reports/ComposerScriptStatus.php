<?php

namespace Kwidoo\HexTools\Reports;

final class ComposerScriptStatus
{
    public function __construct(
        public readonly string $name,
        public readonly bool $exists,
    ) {}
}
