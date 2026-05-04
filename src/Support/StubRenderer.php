<?php

namespace Kwidoo\HexTools\Support;

class StubRenderer
{
    public function render(string $stubPath, array $replacements): string
    {
        $content = file_get_contents($stubPath);

        return $this->renderString($content, $replacements);
    }

    public function renderString(string $template, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $template = str_replace('{{ ' . $key . ' }}', $value, $template);
        }

        return $template;
    }
}
