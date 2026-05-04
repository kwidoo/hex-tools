<?php

beforeEach(function () {
    $this->app['config']->set('hex-tools.phpmd', [
        'enabled' => true,
        'rules' => [
            'main' => ['codesize', 'design', 'naming', 'unusedcode'],
        ],
        'thresholds' => [
            'cyclomatic_complexity_report_level' => 10,
            'npath_complexity_report_level' => 200,
            'excessive_method_length_minimum' => 80,
            'excessive_class_length_minimum' => 400,
            'too_many_methods_maxmethods' => 20,
            'too_many_public_methods_maxmethods' => 15,
            'coupling_between_objects_maximum' => 13,
        ],
    ]);
});

afterEach(function () {
    @unlink($this->app->basePath('phpmd.xml'));
    @unlink($this->app->basePath('custom-phpmd.xml'));
});

it('generates phpmd.xml', function () {
    $this->artisan('hex:phpmd:generate')->assertSuccessful();

    expect(file_exists($this->app->basePath('phpmd.xml')))->toBeTrue();
});

it('fails when file exists without --force', function () {
    $path = $this->app->basePath('phpmd.xml');
    file_put_contents($path, 'original');

    $this->artisan('hex:phpmd:generate')->assertFailed();

    expect(file_get_contents($path))->toBe('original');
});

it('overwrites with --force', function () {
    $path = $this->app->basePath('phpmd.xml');
    file_put_contents($path, 'original');

    $this->artisan('hex:phpmd:generate', ['--force' => true])->assertSuccessful();

    expect(file_get_contents($path))->not->toBe('original');
});

it('respects configured thresholds', function () {
    $this->app['config']->set('hex-tools.phpmd.thresholds.cyclomatic_complexity_report_level', 15);
    $this->app['config']->set('hex-tools.phpmd.thresholds.excessive_class_length_minimum', 500);

    $this->artisan('hex:phpmd:generate')->assertSuccessful();

    $content = file_get_contents($this->app->basePath('phpmd.xml'));
    expect($content)->toContain('value="15"')
        ->and($content)->toContain('value="500"');
});

it('respects custom output path', function () {
    $this->artisan('hex:phpmd:generate', ['--output' => 'custom-phpmd.xml'])->assertSuccessful();

    expect(file_exists($this->app->basePath('custom-phpmd.xml')))->toBeTrue();
});

it('includes extra rules from config', function () {
    $this->app['config']->set('hex-tools.phpmd.rules.main', ['codesize', 'design', 'naming', 'unusedcode', 'cleancode']);

    $this->artisan('hex:phpmd:generate')->assertSuccessful();

    $content = file_get_contents($this->app->basePath('phpmd.xml'));
    expect($content)->toContain('rulesets/cleancode.xml');
});

it('generated xml is well-formed', function () {
    $this->artisan('hex:phpmd:generate')->assertSuccessful();

    $content = file_get_contents($this->app->basePath('phpmd.xml'));
    $xml = simplexml_load_string($content);

    expect($xml)->not->toBeFalse();
});
