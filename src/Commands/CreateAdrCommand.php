<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Generators\AdrGenerator;
use Kwidoo\HexTools\Support\Filesystem;

class CreateAdrCommand extends Command
{
    protected $signature = 'hex:adr:create
        {title : ADR title}
        {--status=proposed : Status: proposed|accepted|deprecated|superseded}';

    protected $description = 'Create a numbered Architecture Decision Record file.';

    public function __construct(
        protected HexToolsConfig $config,
        protected AdrGenerator $generator,
        protected Filesystem $filesystem
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $title = $this->argument('title');
        $status = $this->option('status');
        $adrPath = $this->config->get('paths.adr');

        $this->filesystem->ensureDirectory($adrPath);

        $number = $this->generator->getNextNumber();
        $filename = $this->generator->getFilename($title, $number);
        $filePath = $adrPath . '/' . $filename;

        $content = $this->generator->generate($title, $status);
        $this->filesystem->put($filePath, $content);

        $this->info("ADR created: {$filePath}");

        return self::SUCCESS;
    }
}
