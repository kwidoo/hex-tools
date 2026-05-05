<?php

beforeEach(function () {
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
    @unlink($this->app->basePath('phpmd.xml'));
    @unlink($this->app->basePath('phpmd-domain.xml'));
    @unlink($this->app->basePath('phpmd-application.xml'));
    @unlink($this->app->basePath('docs/architecture/phpmd.md'));
});

it('generates phpmd.xml', function () {
    $this->artisan('hex:phpmd:install')->assertSuccessful();

    expect(file_exists($this->app->basePath('phpmd.xml')))->toBeTrue();
});

it('generates phpmd documentation', function () {
    $this->artisan('hex:phpmd:install')->assertSuccessful();

    expect(file_exists($this->app->basePath('docs/architecture/phpmd.md')))->toBeTrue();
});

it('does not overwrite existing phpmd.xml without --force', function () {
    $path = $this->app->basePath('phpmd.xml');
    file_put_contents($path, 'original');

    $this->artisan('hex:phpmd:install')->assertSuccessful();

    expect(file_get_contents($path))->toBe('original');
});

it('overwrites existing phpmd.xml with --force', function () {
    $path = $this->app->basePath('phpmd.xml');
    file_put_contents($path, 'original');

    $this->artisan('hex:phpmd:install', ['--force' => true])->assertSuccessful();

    expect(file_get_contents($path))->not->toBe('original');
});

it('generates per-layer rulesets with --per-layer option', function () {
    $this->artisan('hex:phpmd:install', ['--per-layer' => true])->assertSuccessful();

    expect(file_exists($this->app->basePath('phpmd.xml')))->toBeTrue()
        ->and(file_exists($this->app->basePath('phpmd-domain.xml')))->toBeTrue()
        ->and(file_exists($this->app->basePath('phpmd-application.xml')))->toBeTrue();
});

it('generates main ruleset with correct rules', function () {
    $this->artisan('hex:phpmd:install', ['--per-layer' => true])->assertSuccessful();

    $content = file_get_contents($this->app->basePath('phpmd.xml'));
    
    expect($content)->toContain('Hex Tools PHPMD Rules (Main)')
        ->and($content)->toContain('rulesets/codesize.xml')
        ->and($content)->toContain('rulesets/design.xml')
        ->and($content)->toContain('rulesets/naming.xml')
        ->and($content)->toContain('rulesets/unusedcode.xml');
});

it('generates domain ruleset with cleancode rule', function () {
    $this->artisan('hex:phpmd:install', ['--per-layer' => true])->assertSuccessful();

    $content = file_get_contents($this->app->basePath('phpmd-domain.xml'));
    
    expect($content)->toContain('Hex Tools PHPMD Rules (Domain)')
        ->and($content)->toContain('PHPMD ruleset for Domain layer')
        ->and($content)->toContain('rulesets/cleancode.xml');
});

it('generates application ruleset with correct description', function () {
    $this->artisan('hex:phpmd:install', ['--per-layer' => true])->assertSuccessful();

    $content = file_get_contents($this->app->basePath('phpmd-application.xml'));
    
    expect($content)->toContain('Hex Tools PHPMD Rules (Application)')
        ->and($content)->toContain('PHPMD ruleset for Application layer');
});

it('does not modify composer.json without --composer-scripts', function () {
    $composerJson = $this->app->basePath('composer.json');
    $original = json_encode(['name' => 'test/app']);
    file_put_contents($composerJson, $original);

    $this->artisan('hex:phpmd:install')->assertSuccessful();

    expect(file_get_contents($composerJson))->toBe($original);

    unlink($composerJson);
});

it('adds phpmd composer scripts when --composer-scripts is passed', function () {
    $composerJson = $this->app->basePath('composer.json');
    file_put_contents($composerJson, json_encode(['name' => 'test/app']));

    $this->artisan('hex:phpmd:install', ['--composer-scripts' => true])->assertSuccessful();

    $data = json_decode(file_get_contents($composerJson), true);
    expect($data['scripts'])->toHaveKey('md')
        ->and($data['scripts'])->toHaveKey('md:domain')
        ->and($data['scripts'])->toHaveKey('md:application')
        ->and($data['scripts'])->toHaveKey('md:baseline')
        ->and($data['scripts'])->toHaveKey('md:update-baseline');

    unlink($composerJson);
});

it('preserves existing scripts when adding phpmd scripts', function () {
    $composerJson = $this->app->basePath('composer.json');
    file_put_contents($composerJson, json_encode([
        'name' => 'test/app',
        'scripts' => ['test' => 'pest', 'stan' => 'vendor/bin/phpstan analyse'],
    ]));

    $this->artisan('hex:phpmd:install', ['--composer-scripts' => true])->assertSuccessful();

    $data = json_decode(file_get_contents($composerJson), true);
    expect($data['scripts']['test'])->toBe('pest')
        ->and($data['scripts']['stan'])->toBe('vendor/bin/phpstan analyse');

    unlink($composerJson);
});

it('shows warning when phpmd binary is missing', function () {
    $this->artisan('hex:phpmd:install')
        ->expectsOutputToContain('PHPMD is not installed')
        ->assertSuccessful();
});

it('generated phpmd.xml contains valid xml', function () {
    $this->artisan('hex:phpmd:install')->assertSuccessful();

    $content = file_get_contents($this->app->basePath('phpmd.xml'));
    $xml = simplexml_load_string($content);

    expect($xml)->not->toBeFalse();
});

it('generated per-layer rulesets contain valid xml', function () {
    $this->artisan('hex:phpmd:install', ['--per-layer' => true])->assertSuccessful();

    foreach (['phpmd.xml', 'phpmd-domain.xml', 'phpmd-application.xml'] as $file) {
        $content = file_get_contents($this->app->basePath($file));
        $xml = simplexml_load_string($content);
        expect($xml)->not->toBeFalse()->withMessage("{$file} is not valid XML");
    }
});
