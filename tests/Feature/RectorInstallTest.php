<?php

afterEach(function () {
    @unlink($this->app->basePath('rector.php'));
    @unlink($this->app->basePath('docs/architecture/rector.md'));
    @unlink($this->app->basePath('composer.json'));
});

it('generates rector.php', function () {
    $this->artisan('hex:rector:install')->assertSuccessful();

    expect(file_exists($this->app->basePath('rector.php')))->toBeTrue();
});

it('generates rector documentation', function () {
    $this->artisan('hex:rector:install')->assertSuccessful();

    expect(file_exists($this->app->basePath('docs/architecture/rector.md')))->toBeTrue();
});

it('does not overwrite existing rector.php without --force', function () {
    $path = $this->app->basePath('rector.php');
    file_put_contents($path, '// original');

    $this->artisan('hex:rector:install')->assertSuccessful();

    expect(file_get_contents($path))->toBe('// original');
});

it('overwrites existing rector.php with --force', function () {
    $path = $this->app->basePath('rector.php');
    file_put_contents($path, '// original');

    $this->artisan('hex:rector:install', ['--force' => true])->assertSuccessful();

    expect(file_get_contents($path))->not->toBe('// original');
});

it('does not modify composer.json without --composer-scripts', function () {
    $composerJson = $this->app->basePath('composer.json');
    $original = json_encode(['name' => 'test/app']);
    file_put_contents($composerJson, $original);

    $this->artisan('hex:rector:install')->assertSuccessful();

    expect(file_get_contents($composerJson))->toBe($original);
});

it('adds rector composer scripts when --composer-scripts is passed', function () {
    $composerJson = $this->app->basePath('composer.json');
    file_put_contents($composerJson, json_encode(['name' => 'test/app']));

    $this->artisan('hex:rector:install', ['--composer-scripts' => true])->assertSuccessful();

    $data = json_decode(file_get_contents($composerJson), true);
    expect($data['scripts'])->toHaveKey('rector')
        ->and($data['scripts'])->toHaveKey('rector:dry')
        ->and($data['scripts']['rector'])->toBe('vendor/bin/rector process')
        ->and($data['scripts']['rector:dry'])->toBe('vendor/bin/rector process --dry-run');
});

it('preserves existing composer scripts when adding rector scripts', function () {
    $composerJson = $this->app->basePath('composer.json');
    file_put_contents($composerJson, json_encode([
        'name' => 'test/app',
        'scripts' => ['test' => 'pest', 'stan' => 'vendor/bin/phpstan analyse'],
    ]));

    $this->artisan('hex:rector:install', ['--composer-scripts' => true])->assertSuccessful();

    $data = json_decode(file_get_contents($composerJson), true);
    expect($data['scripts']['test'])->toBe('pest')
        ->and($data['scripts']['stan'])->toBe('vendor/bin/phpstan analyse');
});

it('shows warning when rector binary is missing', function () {
    $this->artisan('hex:rector:install')
        ->expectsOutputToContain('Rector is not installed')
        ->assertSuccessful();
});

it('generated rector.php contains RectorConfig', function () {
    $this->artisan('hex:rector:install')->assertSuccessful();

    $content = file_get_contents($this->app->basePath('rector.php'));
    expect($content)->toContain('RectorConfig');
});
