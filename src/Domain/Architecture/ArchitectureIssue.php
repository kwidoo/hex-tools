<?php

namespace Kwidoo\HexTools\Domain\Architecture;

class ArchitectureIssue
{
    public function __construct(
        public readonly string $severity,
        public readonly string $code,
        public readonly string $message,
        public readonly ?string $file = null,
        public readonly ?string $module = null,
        public readonly ?string $layer = null,
        public readonly ?string $symbol = null,
        public readonly ?string $baselineStatus = null
    ) {}

    public function withBaselineStatus(?string $status): self
    {
        return new self(
            $this->severity,
            $this->code,
            $this->message,
            $this->file,
            $this->module,
            $this->layer,
            $this->symbol,
            $status
        );
    }

    public function stableHash(): string
    {
        return sha1(implode('|', [
            $this->code,
            $this->file ?? '',
            $this->symbol ?? $this->message,
        ]));
    }

    public function toArray(): array
    {
        return array_filter([
            'severity' => $this->severity,
            'code' => $this->code,
            'message' => $this->message,
            'file' => $this->file,
            'module' => $this->module,
            'layer' => $this->layer,
            'symbol' => $this->symbol,
            'hash' => $this->stableHash(),
            'baseline_status' => $this->baselineStatus,
        ], fn ($value) => $value !== null);
    }
}
