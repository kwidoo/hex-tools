<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->app['config']->set('hex-tools.phpstan', [
        'enabled' => true,
        'paths' => [
            'main' => ['app'],
            'domain' => ['app/Domain'],
            'application' => ['app/Application'],
        ],
        'levels' => ['main' => 5, 'domain' => 8, 'application' => 7],
        'tmp_dirs' => [
            'main' => 'storage/framework/phpstan',
            'domain' => 'storage/framework/phpstan-domain',
            'application' => 'storage/framework/phpstan-application',
        ],
        'exclude_paths' => ['database/migrations/*', 'bootstrap/*', 'storage/*'],
        'includes' => ['vendor/larastan/larastan/extension.neon'],
        'baseline' => 'phpstan-baseline.neon',
        'memory_limit' => '1G',
    ]);
    $this->app['config']->set('hex-tools.phpmd', [
        'enabled' => true,
        'paths' => ['main' => 'app', 'domain' => 'app/Domain', 'application' => 'app/Application'],
        'ruleset' => 'phpmd.xml',
        'baseline' => 'phpmd.baseline.xml',
        'rules' => [
            'main' => ['codesize', 'design', 'naming', 'unusedcode'],
            'domain' => ['codesize', 'design', 'naming', 'unusedcode', 'cleancode'],
            'application' => ['codesize', 'design', 'naming', 'unusedcode'],
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
    $base = $this->app->basePath('');
    $paths = [
        'phpstan.neon.dist', 'phpstan-domain.neon', 'phpstan-application.neon',
        'phpmd.xml', 'pint.json', 'rector.php',
        'deptrac.layers.yaml', 'deptrac.modules.yaml',
        'composer.json',
        'docs/architecture/static-analysis.md',
        'docs/architecture/phpmd.md',
        'docs/architecture/pint.md',
        'docs/architecture/rector.md',
        'docs/architecture/deptrac.md',
        'docs/architecture/ci.md',
    ];
    foreach ($paths as $path) {
        @unlink("{$base}/{$path}");
    }
});

it('installs basic profile successfully', function () {
    $this->artisan('hex:quality:install', ['--profile' => 'basic'])
        ->assertSuccessful()
        ->expectsOutputToContain("Installing quality profile: basic");
});

it('installs strict profile and runs rector install', function () {
    $this->artisan('hex:quality:install', ['--profile' => 'strict'])
        ->assertSuccessful()
        ->expectsOutputToContain("Installing quality profile: strict");

    expect(file_exists($this->app->basePath('rector.php')))->toBeTrue();
});

it('installs ci profile and creates build/architecture directory', function () {
    $this->artisan('hex:quality:install', ['--profile' => 'ci'])
        ->assertSuccessful();

    expect(is_dir($this->app->basePath('build/architecture')))->toBeTrue();
    expect(file_exists($this->app->basePath('docs/architecture/ci.md')))->toBeTrue();
});

it('does not overwrite existing files without --force', function () {
    $path = $this->app->basePath('pint.json');
    file_put_contents($path, 'original');

    $this->artisan('hex:quality:install', ['--profile' => 'basic'])
        ->assertSuccessful();

    expect(file_get_contents($path))->toBe('original');
});

it('overwrites existing files with --force', function () {
    $path = $this->app->basePath('pint.json');
    file_put_contents($path, 'original');

    $this->artisan('hex:quality:install', ['--profile' => 'basic', '--force' => true])
        ->assertSuccessful();

    expect(file_get_contents($path))->not->toBe('original');
});

it('does not modify composer.json without --composer-scripts', function () {
    $composerJson = $this->app->basePath('composer.json');
    $original = json_encode(['name' => 'test/app']);
    file_put_contents($composerJson, $original);

    $this->artisan('hex:quality:install', ['--profile' => 'basic'])->assertSuccessful();

    expect(file_get_contents($composerJson))->toBe($original);
});

it('adds basic composer scripts when --composer-scripts is passed', function () {
    $composerJson = $this->app->basePath('composer.json');
    file_put_contents($composerJson, json_encode(['name' => 'test/app']));

    $this->artisan('hex:quality:install', ['--profile' => 'basic', '--composer-scripts' => true])
        ->assertSuccessful();

    $data = json_decode(file_get_contents($composerJson), true);
    expect($data['scripts'])->toHaveKey('hex:doctor')
        ->and($data['scripts'])->toHaveKey('stan')
        ->and($data['scripts'])->toHaveKey('fmt')
        ->and($data['scripts'])->toHaveKey('fmt:test');
});

it('adds strict composer scripts for strict profile', function () {
    $composerJson = $this->app->basePath('composer.json');
    file_put_contents($composerJson, json_encode(['name' => 'test/app']));

    $this->artisan('hex:quality:install', ['--profile' => 'strict', '--composer-scripts' => true])
        ->assertSuccessful();

    $data = json_decode(file_get_contents($composerJson), true);
    expect($data['scripts'])->toHaveKey('md')
        ->and($data['scripts'])->toHaveKey('rector')
        ->and($data['scripts'])->toHaveKey('rector:dry');
});

it('adds ci composer scripts for ci profile', function () {
    $composerJson = $this->app->basePath('composer.json');
    file_put_contents($composerJson, json_encode(['name' => 'test/app']));

    $this->artisan('hex:quality:install', ['--profile' => 'ci', '--composer-scripts' => true])
        ->assertSuccessful();

    $data = json_decode(file_get_contents($composerJson), true);
    expect($data['scripts'])->toHaveKey('quality')
        ->and($data['scripts'])->toHaveKey('quality:soft')
        ->and($data['scripts'])->toHaveKey('security:audit');
});

it('preserves existing composer scripts', function () {
    $composerJson = $this->app->basePath('composer.json');
    file_put_contents($composerJson, json_encode([
        'name' => 'test/app',
        'scripts' => ['test' => 'pest'],
    ]));

    $this->artisan('hex:quality:install', ['--profile' => 'basic', '--composer-scripts' => true])
        ->assertSuccessful();

    $data = json_decode(file_get_contents($composerJson), true);
    expect($data['scripts']['test'])->toBe('pest');
});
