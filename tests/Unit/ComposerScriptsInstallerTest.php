<?php

use Kwidoo\HexTools\Generators\ComposerScriptsInstaller;

it('adds hex scripts to composer.json', function () {
    $tmpFile = sys_get_temp_dir() . '/composer-test-' . uniqid() . '.json';
    file_put_contents($tmpFile, json_encode(['name' => 'test/app']));

    $installer = new ComposerScriptsInstaller();
    $result = $installer->install($tmpFile);

    expect($result)->toBeTrue();

    $data = json_decode(file_get_contents($tmpFile), true);
    expect($data['scripts'])->toHaveKey('hex:layers')
        ->and($data['scripts'])->toHaveKey('hex:modules');

    unlink($tmpFile);
});

it('does not overwrite existing scripts', function () {
    $tmpFile = sys_get_temp_dir() . '/composer-test-' . uniqid() . '.json';
    file_put_contents($tmpFile, json_encode([
        'name' => 'test/app',
        'scripts' => ['hex:layers' => 'custom-command'],
    ]));

    $installer = new ComposerScriptsInstaller();
    $installer->install($tmpFile);

    $data = json_decode(file_get_contents($tmpFile), true);
    expect($data['scripts']['hex:layers'])->toBe('custom-command');

    unlink($tmpFile);
});

it('returns false when composer.json does not exist', function () {
    $installer = new ComposerScriptsInstaller();
    expect($installer->install('/nonexistent/composer.json'))->toBeFalse();
});
