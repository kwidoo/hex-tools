<?php

namespace Kwidoo\HexTools\Reports;

class DoctorReport
{
    /** @var ToolStatus[] */
    public array $tools = [];

    /** @var ConfigStatus[] */
    public array $configs = [];

    /** @var ConfigStatus[] */
    public array $folders = [];

    /** @var ConfigStatus[] */
    public array $docs = [];

    /** @var string[] */
    public array $suggestions = [];

    /** @var CommandResult[] */
    public array $results = [];

    public function hasMissingImportantItems(): bool
    {
        $missingTools = array_filter($this->tools, fn (ToolStatus $t) => !$t->installed);
        $missingConfigs = array_filter($this->configs, fn (ConfigStatus $c) => !$c->exists);

        return count($missingTools) > 0 || count($missingConfigs) > 0;
    }
}
