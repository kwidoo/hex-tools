<?php

afterEach(function () {
    $base = $this->app->basePath('');
    @unlink("{$base}/build/architecture/reports/architecture-report.md");
    @unlink("{$base}/build/architecture/reports/product-report.md");
    @unlink("{$base}/custom-report.md");
});

it('generates global architecture report', function () {
    $this->artisan('hex:report')->assertSuccessful();

    expect(file_exists($this->app->basePath('build/architecture/reports/architecture-report.md')))->toBeTrue();
});

it('generates module-specific report', function () {
    $this->artisan('hex:report', ['module' => 'Product'])->assertSuccessful();

    expect(file_exists($this->app->basePath('build/architecture/reports/product-report.md')))->toBeTrue();
});

it('writes to custom output path', function () {
    $this->artisan('hex:report', ['--output' => 'custom-report.md'])->assertSuccessful();

    expect(file_exists($this->app->basePath('custom-report.md')))->toBeTrue();
});

it('includes tool availability status in report', function () {
    $this->artisan('hex:report')->assertSuccessful();

    $content = file_get_contents($this->app->basePath('build/architecture/reports/architecture-report.md'));
    expect($content)->toContain('Deptrac')
        ->and($content)->toContain('PHPStan');
});

it('includes config availability in report', function () {
    $this->artisan('hex:report')->assertSuccessful();

    $content = file_get_contents($this->app->basePath('build/architecture/reports/architecture-report.md'));
    expect($content)->toContain('deptrac.layers.yaml')
        ->and($content)->toContain('phpstan.neon.dist');
});

it('includes module map in report', function () {
    $this->artisan('hex:report')->assertSuccessful();

    $content = file_get_contents($this->app->basePath('build/architecture/reports/architecture-report.md'));
    expect($content)->toContain('Product')
        ->and($content)->toContain('Order');
});

it('includes check results when --run-checks is passed with no tools available', function () {
    $this->artisan('hex:report', ['--run-checks' => true])->assertSuccessful();
});

it('outputs json summary when --format=json is passed', function () {
    $this->artisan('hex:report', ['--format' => 'json'])
        ->assertSuccessful()
        ->expectsOutputToContain('"module"');
});

it('outputs info message with report path', function () {
    $this->artisan('hex:report')
        ->assertSuccessful()
        ->expectsOutputToContain('Report generated');
});
