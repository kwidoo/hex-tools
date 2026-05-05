<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Generators\AgentContextGenerator;
use Kwidoo\HexTools\Support\Filesystem;
use Kwidoo\HexTools\Support\Str;

class GenerateAgentContextCommand extends Command
{
    protected $signature = 'hex:agent:context
        {module? : Optional module name}
        {--force : Overwrite existing generated files}
        {--target=all : all|claude|copilot|agents|docs}';

    protected $description = 'Generate AI-agent-friendly architecture context files.';

    public function __construct(
        protected AgentContextGenerator $generator,
        protected Filesystem $filesystem
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $module = (string) $this->argument('module');
        $target = (string) $this->option('target');
        $force = (bool) $this->option('force');

        if ($module) {
            $this->generateModuleContext($module, $force);
            return self::SUCCESS;
        }

        $generalContent = $this->generator->generateGeneral();

        if (in_array($target, ['all', 'agents'])) {
            $this->writeFile(base_path('AGENTS.md'), $generalContent, $force);
        }
        if (in_array($target, ['all', 'claude'])) {
            $this->writeFile(base_path('CLAUDE.md'), $generalContent, $force);
        }
        if (in_array($target, ['all', 'copilot'])) {
            $this->filesystem->ensureDirectory(base_path('.github'));
            $this->writeFile(base_path('.github/copilot-instructions.md'), $generalContent, $force);
        }
        if (in_array($target, ['all', 'docs'])) {
            $this->filesystem->ensureDirectory(base_path('docs/architecture'));
            $this->writeFile(base_path('docs/architecture/ai-context.md'), $generalContent, $force);
        }

        return self::SUCCESS;
    }

    protected function generateModuleContext(string $module, bool $force): void
    {
        $kebab = Str::kebab($module);
        $dir = base_path('docs/architecture/modules');
        $this->filesystem->ensureDirectory($dir);

        $content = $this->generator->generateModuleContext($module);
        $this->writeFile("{$dir}/{$kebab}-ai-context.md", $content, $force);
    }

    protected function writeFile(string $path, string $content, bool $force): void
    {
        $relative = str_replace(base_path('/'), '', $path);

        if (file_exists($path) && !$force) {
            $this->line("Skipped (exists): {$relative}");
            return;
        }

        file_put_contents($path, $content);
        $this->line("Generated: {$relative}");
    }
}
