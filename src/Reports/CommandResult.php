<?php

namespace Kwidoo\HexTools\Reports;

final class CommandResult
{
    public function __construct(
        public readonly string $tool,
        public readonly string $command,
        public readonly int $exitCode,
        public readonly string $output,
    ) {}

    public function passed(): bool
    {
        return $this->exitCode === 0;
    }
}
