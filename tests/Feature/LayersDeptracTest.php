<?php

use Symfony\Component\Yaml\Yaml;

it('generates deptrac.layers.yaml', function () {
    $output = $this->app->basePath('deptrac.layers.yaml');
    @unlink($output);

    $this->artisan('hex:deptrac:layers')->assertSuccessful();

    expect(file_exists($output))->toBeTrue();

    $parsed = Yaml::parseFile($output);
    expect($parsed['deptrac'])->toHaveKey('layers')
        ->and($parsed['deptrac'])->toHaveKey('ruleset');

    unlink($output);
});

it('respects custom output path', function () {
    $output = $this->app->basePath('custom-layers.yaml');
    @unlink($output);

    $this->artisan('hex:deptrac:layers', ['--output' => 'custom-layers.yaml'])->assertSuccessful();

    expect(file_exists($output))->toBeTrue();

    unlink($output);
});

it('does not overwrite without --force', function () {
    $output = $this->app->basePath('deptrac.layers.yaml');
    file_put_contents($output, 'original');

    $this->artisan('hex:deptrac:layers')->assertFailed();

    expect(file_get_contents($output))->toBe('original');

    unlink($output);
});

it('overwrites with --force', function () {
    $output = $this->app->basePath('deptrac.layers.yaml');
    file_put_contents($output, 'original');

    $this->artisan('hex:deptrac:layers', ['--force' => true])->assertSuccessful();

    expect(file_get_contents($output))->not->toBe('original');

    unlink($output);
});

it('generates ruleset that forbids Http depending on Models', function () {
    $output = $this->app->basePath('deptrac.layers.yaml');
    @unlink($output);

    $this->artisan('hex:deptrac:layers')->assertSuccessful();

    $parsed = Yaml::parseFile($output);
    $ruleset = $parsed['deptrac']['ruleset'];

    expect($ruleset['Http'])->not->toContain('Models')
        ->and($ruleset['Http'])->not->toContain('Infrastructure')
        ->and($ruleset['Application'])->not->toContain('Http')
        ->and($ruleset['Application'])->not->toContain('Models')
        ->and($ruleset['Application'])->not->toContain('Infrastructure');

    unlink($output);
});
