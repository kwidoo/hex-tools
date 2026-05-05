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

it('generates JSON report file when --format=json is passed', function () {
    $this->artisan('hex:report', ['--format' => 'json'])->assertSuccessful();

    $path = $this->app->basePath('build/architecture/reports/architecture-report.json');
    expect(file_exists($path))->toBeTrue();

    $content = file_get_contents($path);
    $data = json_decode($content, true);

    expect($data)->toBeArray()
        ->and($data)->toHaveKeys(['module_name', 'generated_at', 'architecture_summary', 'violations', 'dependencies', 'quality_check_summary'])
        ->and($data['module_name'])->toBe('all')
        ->and($data['generated_at'])->not->toBeEmpty();
});

it('generates module-specific JSON report', function () {
    $this->artisan('hex:report', ['module' => 'Product', '--format' => 'json'])->assertSuccessful();

    $path = $this->app->basePath('build/architecture/reports/product-report.json');
    expect(file_exists($path))->toBeTrue();

    $content = file_get_contents($path);
    $data = json_decode($content, true);

    expect($data['module_name'])->toBe('Product');
});

it('generates Markdown report file by default', function () {
    $this->artisan('hex:report')->assertSuccessful();

    $path = $this->app->basePath('build/architecture/reports/architecture-report.md');
    expect(file_exists($path))->toBeTrue();

    $content = file_get_contents($path);
    expect($content)->toContain('| Tool | Status |');
});

it('fails with unsupported format', function () {
    $this->artisan('hex:report', ['--format' => 'xml'])
        ->assertFailed()
        ->expectsOutputToContain("Unsupported format 'xml'");
});
