<?php

use Symfony\Component\Yaml\Yaml;

it('generates deptrac.modules.yaml', function () {
    $output = $this->app->basePath('deptrac.modules.yaml');
    @unlink($output);

    $this->artisan('hex:deptrac:modules')->assertSuccessful();

    expect(file_exists($output))->toBeTrue();

    $parsed = Yaml::parseFile($output);
    $layerNames = array_column($parsed['deptrac']['layers'], 'name');

    expect($layerNames)->toContain('Example')
        ->and($layerNames)->toContain('Shared');

    unlink($output);
});

it('does not overwrite without --force', function () {
    $output = $this->app->basePath('deptrac.modules.yaml');
    file_put_contents($output, 'original');

    $this->artisan('hex:deptrac:modules')->assertFailed();

    expect(file_get_contents($output))->toBe('original');

    unlink($output);
});

it('overwrites with --force', function () {
    $output = $this->app->basePath('deptrac.modules.yaml');
    file_put_contents($output, 'original');

    $this->artisan('hex:deptrac:modules', ['--force' => true])->assertSuccessful();

    expect(file_get_contents($output))->not->toBe('original');

    unlink($output);
});

it('uses module rules from config', function () {
    $output = $this->app->basePath('deptrac.modules.yaml');
    @unlink($output);

    $this->artisan('hex:deptrac:modules')->assertSuccessful();

    $parsed = Yaml::parseFile($output);
    $ruleset = $parsed['deptrac']['ruleset'];

    // Example may depend on Shared (but not itself in ruleset)
    expect($ruleset['Example'])->toContain('Shared')
        ->and($ruleset['Example'])->not->toContain('Example');

    unlink($output);
});

it('contains no business-specific module names in default config', function () {
    $config = include __DIR__ . '/../../config/hex-tools.php';

    $businessSpecificModules = [
        'Billing',
        'Category',
        'Farmer',
        'Notification',
        'Offer',
        'Order',
        'Payment',
        'Pickup',
        'Product',
        'User',
    ];

    foreach ($businessSpecificModules as $module) {
        expect($config['modules'])->not->toContain($module);
        expect(array_keys($config['module_rules']))->not->toContain($module);
    }
});

it('does not crash when modules is empty', function () {
    config()->set('hex-tools.modules', []);
    config()->set('hex-tools.module_rules', []);

    $this->app->forgetInstance(\Kwidoo\HexTools\Config\HexToolsConfig::class);
    $this->app->forgetInstance(\Kwidoo\HexTools\Generators\DeptracModulesGenerator::class);

    $output = $this->app->basePath('deptrac.modules.yaml');
    @unlink($output);

    $this->artisan('hex:deptrac:modules')->assertSuccessful();

    expect(file_exists($output))->toBeTrue();

    $parsed = Yaml::parseFile($output);
    expect($parsed['deptrac']['layers'])->toBeEmpty();
    expect($parsed['deptrac']['ruleset'])->toBeEmpty();

    unlink($output);
});

it('includes all modules in ruleset even without explicit module_rules', function () {
    config()->set('hex-tools.modules', ['Product', 'Order', 'Shared']);
    config()->set('hex-tools.module_rules', [
        'Product' => ['Product', 'Shared'],
    ]);

    $this->app->forgetInstance(\Kwidoo\HexTools\Config\HexToolsConfig::class);
    $this->app->forgetInstance(\Kwidoo\HexTools\Generators\DeptracModulesGenerator::class);

    $output = $this->app->basePath('deptrac.modules.yaml');
    @unlink($output);

    $this->artisan('hex:deptrac:modules')->assertSuccessful();

    $parsed = Yaml::parseFile($output);
    $ruleset = $parsed['deptrac']['ruleset'];

    expect(array_keys($ruleset))->toContain('Product')
        ->and($ruleset)->toContainKey('Order')
        ->and($ruleset)->toContainKey('Shared');

    // Product should have Shared as dependency (not itself)
    expect($ruleset['Product'])->toContain('Shared')
        ->and($ruleset['Product'])->not->toContain('Product');

    // Order and Shared should have null (no external dependencies)
    expect($ruleset['Order'])->toBeNull()
        ->and($ruleset['Shared'])->toBeNull();

    unlink($output);
});

it('does not emit self-dependency as external dependency', function () {
    config()->set('hex-tools.modules', ['Product']);
    config()->set('hex-tools.module_rules', [
        'Product' => ['Product'],
    ]);

    $this->app->forgetInstance(\Kwidoo\HexTools\Config\HexToolsConfig::class);
    $this->app->forgetInstance(\Kwidoo\HexTools\Generators\DeptracModulesGenerator::class);

    $output = $this->app->basePath('deptrac.modules.yaml');
    @unlink($output);

    $this->artisan('hex:deptrac:modules')->assertSuccessful();

    $parsed = Yaml::parseFile($output);
    $ruleset = $parsed['deptrac']['ruleset'];

    expect($ruleset['Product'])->toBeNull();

    unlink($output);
});
