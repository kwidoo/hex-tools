<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Generators\CiGenerator;
use Kwidoo\HexTools\Support\Filesystem;

class GenerateCiCommand extends Command
{
    protected $signature = 'hex:ci:generate
        {--provider=github : github|drone}
        {--force : Overwrite existing CI file}
        {--output= : Custom output path}';

    protected $description = 'Generate a CI quality workflow file.';

    public function __construct(
        protected CiGenerator $generator,
        protected Filesystem $filesystem
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $provider = (string) $this->option('provider');
        $force = (bool) $this->option('force');

        [$content, $defaultPath] = match ($provider) {
            'drone' => [$this->generator->generateDrone(), '.drone.hex-quality.yml'],
            default => [$this->generator->generateGithub(), '.github/workflows/quality.yml'],
        };

        $outputPath = $this->option('output')
            ? base_path((string) $this->option('output'))
            : base_path($defaultPath);

        if (file_exists($outputPath) && !$force) {
            $this->error("File exists (use --force to overwrite): {$defaultPath}");
            return self::FAILURE;
        }

        $this->filesystem->ensureDirectory(dirname($outputPath));
        file_put_contents($outputPath, $content);

        $relative = str_replace(base_path('/'), '', $outputPath);
        $this->line("Generated: {$relative}");

        return self::SUCCESS;
    }
}
