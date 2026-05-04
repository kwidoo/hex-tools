<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Support\ProcessRunner;
use Kwidoo\HexTools\Support\ToolAvailability;

class GeneratePhpStanBaselineCommand extends Command
{
    protected $signature = 'hex:phpstan:baseline
        {--run : Actually run PHPStan baseline generation}
        {--config=phpstan.neon.dist : PHPStan config file}
        {--memory=1G : Memory limit}';

    protected $description = 'Print or run the PHPStan baseline generation command.';

    public function __construct(
        protected ProcessRunner $processRunner,
        protected ToolAvailability $toolAvailability
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $config = (string) $this->option('config');
        $memory = (string) $this->option('memory');
        $command = "vendor/bin/phpstan analyse --configuration={$config} --generate-baseline --memory-limit={$memory}";

        if (!$this->option('run')) {
            $this->line('Run the following command to generate a PHPStan baseline:');
            $this->line('');
            $this->line("  {$command}");
            $this->line('');
            return self::SUCCESS;
        }

        if (!$this->toolAvailability->hasPhpStan()) {
            $this->error('vendor/bin/phpstan not found.');
            $this->line('');
            $this->line('Install PHPStan with:');
            $this->line('');
            $this->line('  composer require --dev larastan/larastan');
            $this->line('');
            return self::FAILURE;
        }

        $this->line("Running: {$command}");

        $exitCode = $this->processRunner->run(
            $command,
            function (string $type, string $buffer) {
                $this->output->write($buffer);
            }
        );

        return $exitCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
