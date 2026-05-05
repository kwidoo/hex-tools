<?php

namespace Kwidoo\HexTools\Domain\Architecture;

class DoctorCheck
{
    public function __construct(
        public readonly string $status,
        public readonly string $code,
        public readonly string $message,
        public readonly ?string $path = null
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'status' => $this->status,
            'code' => $this->code,
            'message' => $this->message,
            'path' => $this->path,
        ], fn ($value) => $value !== null);
    }
}
