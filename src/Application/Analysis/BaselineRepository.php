<?php

namespace Kwidoo\HexTools\Application\Analysis;

use Kwidoo\HexTools\Domain\Architecture\ArchitectureIssue;
use Kwidoo\HexTools\Domain\Architecture\ArchitectureReport;
use Kwidoo\HexTools\Support\Filesystem;

class BaselineRepository
{
    public function __construct(protected Filesystem $filesystem) {}

    /** @return array<string, array> */
    public function hashes(string $path): array
    {
        if (!$this->filesystem->exists($path)) {
            return [];
        }

        $data = json_decode($this->filesystem->get($path), true);
        if (!is_array($data)) {
            return [];
        }

        $hashes = [];
        foreach ($data['issues'] ?? [] as $issue) {
            if (isset($issue['hash'])) {
                $hashes[$issue['hash']] = $issue;
            }
        }

        return $hashes;
    }

    public function write(string $path, ArchitectureReport $report): void
    {
        $directory = dirname($path);
        $this->filesystem->ensureDirectory($directory);

        $issues = array_map(fn (ArchitectureIssue $issue) => [
            'code' => $issue->code,
            'file' => $issue->file,
            'message' => $issue->message,
            'hash' => $issue->stableHash(),
        ], $report->issues());

        $this->filesystem->put($path, json_encode([
            'generatedAt' => date(DATE_ATOM),
            'issues' => $issues,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }
}
