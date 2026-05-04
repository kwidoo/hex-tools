<?php

use Illuminate\Support\Facades\File;

it('publishes the config file', function () {
    $configPath = config_path('hex-tools.php');

    File::delete($configPath);

    $this->artisan('hex:install')->assertSuccessful();

    expect(file_exists($configPath))->toBeTrue();
});

it('creates architecture directories', function () {
    $docs = $this->app->basePath('docs/architecture');
    $adr = $this->app->basePath('docs/adr');
    $build = $this->app->basePath('build/architecture');

    $this->artisan('hex:install')->assertSuccessful();

    expect(is_dir($docs))->toBeTrue()
        ->and(is_dir($adr))->toBeTrue()
        ->and(is_dir($build))->toBeTrue();
});

it('installs composer scripts when flag is passed', function () {
    $composerJson = $this->app->basePath('composer.json');
    file_put_contents($composerJson, json_encode(['name' => 'test/app']));

    $this->artisan('hex:install', ['--composer-scripts' => true])->assertSuccessful();

    $data = json_decode(file_get_contents($composerJson), true);
    expect($data['scripts'])->toHaveKey('hex:layers');

    unlink($composerJson);
});

it('does not modify composer.json without --composer-scripts', function () {
    $composerJson = $this->app->basePath('composer.json');
    $original = json_encode(['name' => 'test/app']);
    file_put_contents($composerJson, $original);

    $this->artisan('hex:install')->assertSuccessful();

    expect(file_get_contents($composerJson))->toBe($original);

    unlink($composerJson);
});
