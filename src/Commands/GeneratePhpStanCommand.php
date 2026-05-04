<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Generators\PhpStanConfigGenerator;

class GeneratePhpStanCommand extends Command
{
    protected $signature = 'hex:phpstan:generate
        {--force : Overwrite existing files}
        {--main=phpstan.neon.dist : Main PHPStan config output path}
        {--domain=phpstan-domain.neon : Domain PHPStan config output path}
        {--application=phpstan-application.neon : Application PHPStan config output path}
        {--with-baseline : Include phpstan-baseline.neon in the main config}';

    protected $description = 'Generate or regenerate PHPStan config files.';

    public function __construct(protected PhpStanConfigGenerator $generator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $withBaseline = (bool) $this->option('with-baseline');

        $files = [
            (string) $this->option('main') => $this->generator->generateMain($withBaseline),
            (string) $this->option('domain') => $this->generator->generateDomain(),
            (string) $this->option('application') => $this->generator->generateApplication(),
        ];

        foreach ($files as $path => $content) {
            $fullPath = base_path($path);

            if (file_exists($fullPath) && !$force) {
                $this->error("File exists (use --force to overwrite): {$path}");
                return self::FAILURE;
            }

            file_put_contents($fullPath, $content);
            $this->line("Generated: {$path}");
        }

        return self::SUCCESS;
    }
}
