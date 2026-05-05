<?php

namespace Kwidoo\HexTools\Domain\Architecture;

class DoctorReport
{
    /** @param array<DoctorCheck> $checks */
    public function __construct(public readonly array $checks) {}

    public function hasFailures(bool $strict = false): bool
    {
        foreach ($this->checks as $check) {
            if ($check->status === 'fail' || ($strict && $check->status === 'warn')) {
                return true;
            }
        }

        return false;
    }

    public function summary(): array
    {
        return [
            'checks' => count($this->checks),
            'ok' => count(array_filter($this->checks, fn (DoctorCheck $check) => $check->status === 'ok')),
            'warnings' => count(array_filter($this->checks, fn (DoctorCheck $check) => $check->status === 'warn')),
            'failures' => count(array_filter($this->checks, fn (DoctorCheck $check) => $check->status === 'fail')),
        ];
    }

    public function toArray(): array
    {
        return [
            'checks' => array_map(fn (DoctorCheck $check) => $check->toArray(), $this->checks),
            'summary' => $this->summary(),
        ];
    }
}
