<?php

namespace Kwidoo\HexTools\Commands;

use Illuminate\Console\Command;
use Kwidoo\HexTools\Application\Analysis\ExplanationProvider;
use Kwidoo\HexTools\Application\Reports\ArchitectureReportFormatter;

class ExplainCommand extends Command
{
    protected $signature = 'hex:explain {rule-or-code : Architecture rule code to explain}';

    protected $description = 'Explain an architecture rule in practical language.';

    public function __construct(
        protected ExplanationProvider $provider,
        protected ArchitectureReportFormatter $formatter
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $code = $this->argument('rule-or-code');
        $explanation = $this->provider->explain($code);

        if ($explanation === null) {
            $this->warn("Unknown architecture rule: {$code}");
            $this->line('Known rules can be inspected with docs/architecture-rules.md or generated architecture reports.');
            return self::FAILURE;
        }

        foreach (explode("\n", rtrim($this->formatter->explanation($explanation))) as $line) {
            $this->line($line);
        }

        return self::SUCCESS;
    }
}
