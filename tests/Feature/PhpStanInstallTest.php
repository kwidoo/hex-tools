<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $app = $this->app;
    $app['config']->set('hex-tools.phpstan', [
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
    @unlink($this->app->basePath('docs/architecture/static-analysis.md'));
});

it('generates phpstan config files', function () {
    $this->artisan('hex:phpstan:install')->assertSuccessful();

    expect(file_exists($this->app->basePath('phpstan.neon.dist')))->toBeTrue()
        ->and(file_exists($this->app->basePath('phpstan-domain.neon')))->toBeTrue()
        ->and(file_exists($this->app->basePath('phpstan-application.neon')))->toBeTrue();
});

it('generates static analysis docs', function () {
    $this->artisan('hex:phpstan:install')->assertSuccessful();

    expect(file_exists($this->app->basePath('docs/architecture/static-analysis.md')))->toBeTrue();
});

it('does not overwrite existing files without --force', function () {
    $path = $this->app->basePath('phpstan.neon.dist');
    file_put_contents($path, 'original');

    $this->artisan('hex:phpstan:install')->assertSuccessful();

    expect(file_get_contents($path))->toBe('original');
});

it('overwrites existing files with --force', function () {
    $path = $this->app->basePath('phpstan.neon.dist');
    file_put_contents($path, 'original');

    $this->artisan('hex:phpstan:install', ['--force' => true])->assertSuccessful();

    expect(file_get_contents($path))->not->toBe('original');
});

it('does not modify composer.json without --composer-scripts', function () {
    $composerJson = $this->app->basePath('composer.json');
    $original = json_encode(['name' => 'test/app']);
    file_put_contents($composerJson, $original);

    $this->artisan('hex:phpstan:install')->assertSuccessful();

    expect(file_get_contents($composerJson))->toBe($original);

    unlink($composerJson);
});

it('updates composer.json when --composer-scripts is passed', function () {
    $composerJson = $this->app->basePath('composer.json');
    file_put_contents($composerJson, json_encode(['name' => 'test/app']));

    $this->artisan('hex:phpstan:install', ['--composer-scripts' => true])->assertSuccessful();

    $data = json_decode(file_get_contents($composerJson), true);
    expect($data['scripts'])->toHaveKey('stan')
        ->and($data['scripts'])->toHaveKey('stan:domain')
        ->and($data['scripts'])->toHaveKey('stan:application')
        ->and($data['scripts'])->toHaveKey('stan:baseline');

    unlink($composerJson);
});

it('preserves existing composer scripts when adding phpstan scripts', function () {
    $composerJson = $this->app->basePath('composer.json');
    file_put_contents($composerJson, json_encode([
        'name' => 'test/app',
        'scripts' => ['test' => 'pest'],
    ]));

    $this->artisan('hex:phpstan:install', ['--composer-scripts' => true])->assertSuccessful();

    $data = json_decode(file_get_contents($composerJson), true);
    expect($data['scripts'])->toHaveKey('test')
        ->and($data['scripts']['test'])->toBe('pest');

    unlink($composerJson);
});

it('includes baseline in main config when --with-baseline is passed', function () {
    $this->artisan('hex:phpstan:install', ['--with-baseline' => true])->assertSuccessful();

    $content = file_get_contents($this->app->basePath('phpstan.neon.dist'));
    expect($content)->toContain('phpstan-baseline.neon');
});

it('does not include baseline in main config without --with-baseline', function () {
    $this->artisan('hex:phpstan:install')->assertSuccessful();

    $content = file_get_contents($this->app->basePath('phpstan.neon.dist'));
    expect($content)->not->toContain('phpstan-baseline.neon');
});

it('shows warning when phpstan binary is missing', function () {
    $this->artisan('hex:phpstan:install')
        ->expectsOutputToContain('PHPStan/Larastan is not installed')
        ->assertSuccessful();
});
