<?php

namespace Kwidoo\HexTools\Domain\Architecture;

class RuleExplanation
{
    public function __construct(
        public readonly string $code,
        public readonly string $why,
        public readonly string $badExample,
        public readonly string $betterApproach,
        public readonly string $suggestedStructure
    ) {}

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'why' => $this->why,
            'bad_example' => $this->badExample,
            'better_approach' => $this->betterApproach,
            'suggested_structure' => $this->suggestedStructure,
        ];
    }
}
