<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Generators\DeptracModulesGenerator;
use Kwidoo\HexTools\Support\Filesystem;

class GenerateModulesDeptracCommand extends Command
{
    protected $signature = 'hex:deptrac:modules
        {--output=deptrac.modules.yaml : Output file path}
        {--force : Overwrite existing file}
        {--dry-run : Preview changes without writing files}
        {--no-overwrite : Skip existing files without prompting}';

    protected $description = 'Generate deptrac.modules.yaml for business module boundary checks.';

    public function __construct(
        protected DeptracModulesGenerator $generator,
        protected Filesystem $filesystem
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $output = base_path($this->option('output'));
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');
        $noOverwrite = (bool) $this->option('no-overwrite');

        if ($dryRun) {
            $this->info('[DRY RUN] No files will be written.');
            $this->line('');
        }

        if ($this->filesystem->exists($output) && $noOverwrite) {
            $this->line("Skipped (exists): {$output}");
            if ($dryRun) {
                $this->line('');
                $this->info('[DRY RUN] Completed. No changes were made.');
            }
            return self::SUCCESS;
        }

        if ($this->filesystem->exists($output) && !$force) {
            if ($dryRun) {
                $this->warn("[DRY RUN] Would overwrite: {$output}");
                $this->line('');
                $this->info('[DRY RUN] Completed. No changes were made.');
            } else {
                $this->error("File already exists: {$output}");
                $this->line('Use --force to overwrite.');
            }
            return $dryRun ? self::SUCCESS : self::FAILURE;
        }

        if (!file_exists(base_path('vendor/bin/deptrac'))) {
            $this->warn('Deptrac is not installed. Run:');
            $this->line('composer require --dev qossmic/deptrac');
        }

        $content = $this->generator->generate();
        
        if ($dryRun) {
            $this->line("[DRY RUN] Would generate: {$output}");
            $this->line('');
            $this->info('[DRY RUN] Completed. No changes were made.');
        } else {
            $this->filesystem->put($output, $content);
            $this->info("Generated: {$output}");
        }

        return self::SUCCESS;
    }
}
