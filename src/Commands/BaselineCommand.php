<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Application\Analysis\ArchitectureInspector;
use Kwidoo\HexTools\Application\Analysis\BaselineRepository;
use Kwidoo\HexTools\Config\HexToolsConfig;

class BaselineCommand extends Command
{
    protected $signature = 'hex:baseline
        {--output= : Baseline output file}
        {--format=json : Baseline format}
        {--update : Overwrite an existing baseline}
        {--dry-run : Preview changes without writing files}';

    protected $description = 'Create a stable baseline of current architecture issues.';

    public function __construct(
        protected HexToolsConfig $config,
        protected ArchitectureInspector $inspector,
        protected BaselineRepository $baseline
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('format') !== 'json') {
            $this->error('Only JSON baselines are supported.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('[DRY RUN] No files will be written.');
            $this->line('');
        }

        $path = $this->option('output') ?: $this->config->get('baseline.path', base_path('.hex/baseline/architecture.json'));
        if (is_file($path) && !$this->option('update')) {
            $this->warn("Baseline already exists: {$path}");
            return self::SUCCESS;
        }

        $report = $this->inspector->inspect(null, false);
        
        if ($dryRun) {
            $issueCount = count($report->violations() ?? []);
            $this->line("[DRY RUN] Would write baseline with {$issueCount} issue(s) to: {$path}");
            $this->line('');
            $this->info('[DRY RUN] Completed. No changes were made.');
        } else {
            $this->baseline->write($path, $report);
            $this->info("Architecture baseline written: {$path}");
        }

        return self::SUCCESS;
    }
}
