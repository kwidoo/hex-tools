<?php

use Kwidoo\HexTools\Generators\PhpStanComposerScriptsInstaller;

it('adds phpstan scripts to composer.json', function () {
    $tmpFile = sys_get_temp_dir() . '/composer-phpstan-test-' . uniqid() . '.json';
    file_put_contents($tmpFile, json_encode(['name' => 'test/app']));

    $installer = new PhpStanComposerScriptsInstaller();
    $result = $installer->install($tmpFile);

    expect($result)->toBeTrue();

    $data = json_decode(file_get_contents($tmpFile), true);
    expect($data['scripts'])->toHaveKey('stan')
        ->and($data['scripts'])->toHaveKey('stan:domain')
        ->and($data['scripts'])->toHaveKey('stan:application')
        ->and($data['scripts'])->toHaveKey('stan:baseline');

    unlink($tmpFile);
});

it('does not overwrite existing phpstan scripts', function () {
    $tmpFile = sys_get_temp_dir() . '/composer-phpstan-test-' . uniqid() . '.json';
    file_put_contents($tmpFile, json_encode([
        'name' => 'test/app',
        'scripts' => ['stan' => 'my-custom-stan-command'],
    ]));

    $installer = new PhpStanComposerScriptsInstaller();
    $installer->install($tmpFile);

    $data = json_decode(file_get_contents($tmpFile), true);
    expect($data['scripts']['stan'])->toBe('my-custom-stan-command');

    unlink($tmpFile);
});

it('preserves existing non-phpstan scripts', function () {
    $tmpFile = sys_get_temp_dir() . '/composer-phpstan-test-' . uniqid() . '.json';
    file_put_contents($tmpFile, json_encode([
        'name' => 'test/app',
        'scripts' => ['test' => 'pest', 'hex:layers' => 'vendor/bin/deptrac analyse'],
    ]));

    $installer = new PhpStanComposerScriptsInstaller();
    $installer->install($tmpFile);

    $data = json_decode(file_get_contents($tmpFile), true);
    expect($data['scripts'])->toHaveKey('test')
        ->and($data['scripts']['test'])->toBe('pest')
        ->and($data['scripts'])->toHaveKey('hex:layers');

    unlink($tmpFile);
});

it('returns false when composer.json does not exist', function () {
    $installer = new PhpStanComposerScriptsInstaller();
    expect($installer->install('/nonexistent/composer.json'))->toBeFalse();
});
