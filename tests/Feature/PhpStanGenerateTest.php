<?php

beforeEach(function () {
    $this->app['config']->set('hex-tools.phpstan', [
        'enabled' => true,
        'paths' => [
            'main' => ['app'],
            'domain' => ['app/Domain'],
            'application' => ['app/Application'],
        ],
        'levels' => [
            'main' => 5,
            'domain' => 8,
            'application' => 7,
        ],
        'tmp_dirs' => [
            'main' => 'storage/framework/phpstan',
            'domain' => 'storage/framework/phpstan-domain',
            'application' => 'storage/framework/phpstan-application',
        ],
        'exclude_paths' => [
            'database/migrations/*',
            'database/seeders/*',
            'bootstrap/*',
            'storage/*',
        ],
        'includes' => [
            'vendor/larastan/larastan/extension.neon',
            'vendor/nesbot/carbon/extension.neon',
        ],
        'baseline' => 'phpstan-baseline.neon',
        'memory_limit' => '1G',
    ]);
});

afterEach(function () {
    @unlink($this->app->basePath('phpstan.neon.dist'));
    @unlink($this->app->basePath('phpstan-domain.neon'));
    @unlink($this->app->basePath('phpstan-application.neon'));
    @unlink($this->app->basePath('custom-main.neon'));
});

it('generates all three phpstan config files', function () {
    $this->artisan('hex:phpstan:generate')->assertSuccessful();

    expect(file_exists($this->app->basePath('phpstan.neon.dist')))->toBeTrue()
        ->and(file_exists($this->app->basePath('phpstan-domain.neon')))->toBeTrue()
        ->and(file_exists($this->app->basePath('phpstan-application.neon')))->toBeTrue();
});

it('fails when file exists without --force', function () {
    $path = $this->app->basePath('phpstan.neon.dist');
    file_put_contents($path, 'original');

    $this->artisan('hex:phpstan:generate')->assertFailed();

    expect(file_get_contents($path))->toBe('original');
});

it('overwrites files with --force', function () {
    file_put_contents($this->app->basePath('phpstan.neon.dist'), 'original');
    file_put_contents($this->app->basePath('phpstan-domain.neon'), 'original');
    file_put_contents($this->app->basePath('phpstan-application.neon'), 'original');

    $this->artisan('hex:phpstan:generate', ['--force' => true])->assertSuccessful();

    expect(file_get_contents($this->app->basePath('phpstan.neon.dist')))->not->toBe('original');
});

it('respects configured levels', function () {
    $this->artisan('hex:phpstan:generate')->assertSuccessful();

    $main = file_get_contents($this->app->basePath('phpstan.neon.dist'));
    $domain = file_get_contents($this->app->basePath('phpstan-domain.neon'));
    $application = file_get_contents($this->app->basePath('phpstan-application.neon'));

    expect($main)->toContain('level: 5')
        ->and($domain)->toContain('level: 8')
        ->and($application)->toContain('level: 7');
});

it('respects configured paths', function () {
    $this->artisan('hex:phpstan:generate')->assertSuccessful();

    $main = file_get_contents($this->app->basePath('phpstan.neon.dist'));
    $domain = file_get_contents($this->app->basePath('phpstan-domain.neon'));
    $application = file_get_contents($this->app->basePath('phpstan-application.neon'));

    expect($main)->toContain('- app')
        ->and($domain)->toContain('- app/Domain')
        ->and($application)->toContain('- app/Application');
});

it('respects custom output paths', function () {
    $customPath = $this->app->basePath('custom-main.neon');

    $this->artisan('hex:phpstan:generate', [
        '--main' => 'custom-main.neon',
        '--domain' => 'phpstan-domain.neon',
        '--application' => 'phpstan-application.neon',
    ])->assertSuccessful();

    expect(file_exists($customPath))->toBeTrue();
});

it('includes baseline when --with-baseline is passed', function () {
    $this->artisan('hex:phpstan:generate', ['--with-baseline' => true])->assertSuccessful();

    $content = file_get_contents($this->app->basePath('phpstan.neon.dist'));
    expect($content)->toContain('phpstan-baseline.neon');
});

it('does not include baseline without --with-baseline', function () {
    $this->artisan('hex:phpstan:generate')->assertSuccessful();

    $content = file_get_contents($this->app->basePath('phpstan.neon.dist'));
    expect($content)->not->toContain('phpstan-baseline.neon');
});
