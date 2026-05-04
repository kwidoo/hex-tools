<?php

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Generators\MermaidModuleGraphGenerator;
use Kwidoo\HexTools\Support\StubRenderer;

function makeModuleGenerator(array $moduleRules): MermaidModuleGraphGenerator
{
    $config = new HexToolsConfig([
        'modules' => array_keys($moduleRules),
        'module_rules' => $moduleRules,
        'layers' => [],
        'paths' => ['docs' => sys_get_temp_dir()],
    ]);

    return new MermaidModuleGraphGenerator($config, new StubRenderer());
}

it('generates graph TD header', function () {
    $gen = makeModuleGenerator(['Shared' => ['Shared']]);
    expect($gen->generate())->toContain('graph TD');
});

it('emits an edge for each inter-module dependency', function () {
    $gen = makeModuleGenerator([
        'Shared' => ['Shared'],
        'User' => ['User', 'Shared'],
    ]);

    $output = $gen->generate();
    expect($output)->toContain('User --> Shared')
        ->and($output)->not->toContain('User --> User')
        ->and($output)->not->toContain('Shared --> Shared');
});

it('renders a standalone node for modules with no outgoing dependencies', function () {
    $gen = makeModuleGenerator(['Shared' => ['Shared']]);
    expect($gen->generate())->toContain('    Shared');
});

it('emits multiple edges for modules with several dependencies', function () {
    $gen = makeModuleGenerator([
        'Shared' => ['Shared'],
        'Product' => ['Product', 'Category', 'Shared'],
    ]);

    $output = $gen->generate();
    expect($output)->toContain('Product --> Category')
        ->and($output)->toContain('Product --> Shared');
});
