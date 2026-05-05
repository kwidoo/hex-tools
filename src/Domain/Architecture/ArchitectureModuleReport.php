<?php

namespace Kwidoo\HexTools\Domain\Architecture;

class ArchitectureModuleReport
{
    /**
     * @param array<string> $layers
     * @param array<ArchitectureIssue> $issues
     * @param array<string> $suggestions
     * @param array<string> $publicApiCandidates
     * @param array<string> $incomingDependencies
     * @param array<string> $outgoingDependencies
     */
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly array $layers,
        public readonly array $issues = [],
        public readonly array $suggestions = [],
        public readonly array $publicApiCandidates = [],
        public readonly array $incomingDependencies = [],
        public readonly array $outgoingDependencies = []
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'path' => $this->path,
            'layers' => $this->layers,
            'issues' => array_map(fn (ArchitectureIssue $issue) => $issue->toArray(), $this->issues),
            'suggestions' => $this->suggestions,
            'public_api_candidates' => $this->publicApiCandidates,
            'incoming_dependencies' => $this->incomingDependencies,
            'outgoing_dependencies' => $this->outgoingDependencies,
        ];
    }
}
