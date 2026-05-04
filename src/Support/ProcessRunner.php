<?php

namespace Kwidoo\HexTools\Support;

use Symfony\Component\Process\Process;

class ProcessRunner
{
    public function run(string $command, ?callable $output = null): int
    {
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(null);

        return $process->run($output);
    }
}
