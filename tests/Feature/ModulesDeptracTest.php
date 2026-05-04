<?php

use Symfony\Component\Yaml\Yaml;

it('generates deptrac.modules.yaml', function () {
    $output = $this->app->basePath('deptrac.modules.yaml');
    @unlink($output);

    $this->artisan('hex:deptrac:modules')->assertSuccessful();

    expect(file_exists($output))->toBeTrue();

    $parsed = Yaml::parseFile($output);
    $layerNames = array_column($parsed['deptrac']['layers'], 'name');

    expect($layerNames)->toContain('Product')
        ->and($layerNames)->toContain('Order')
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

    // Order may depend on Product and Shared (but not itself in ruleset)
    expect($ruleset['Order'])->toContain('Product')
        ->and($ruleset['Order'])->toContain('Shared')
        ->and($ruleset['Order'])->not->toContain('Order');

    unlink($output);
});
