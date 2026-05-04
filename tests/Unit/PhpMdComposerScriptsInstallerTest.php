<?php

use Kwidoo\HexTools\Generators\PhpMdComposerScriptsInstaller;

it('adds phpmd scripts to composer.json', function () {
    $tmpFile = sys_get_temp_dir() . '/composer-phpmd-test-' . uniqid() . '.json';
    file_put_contents($tmpFile, json_encode(['name' => 'test/app']));

    $installer = new PhpMdComposerScriptsInstaller();
    $result = $installer->install($tmpFile);

    expect($result)->toBeTrue();

    $data = json_decode(file_get_contents($tmpFile), true);
    expect($data['scripts'])->toHaveKey('md')
        ->and($data['scripts'])->toHaveKey('md:domain')
        ->and($data['scripts'])->toHaveKey('md:application')
        ->and($data['scripts'])->toHaveKey('md:baseline')
        ->and($data['scripts'])->toHaveKey('md:update-baseline')
        ->and($data['scripts'])->toHaveKey('md:report');

    unlink($tmpFile);
});

it('does not overwrite existing phpmd scripts', function () {
    $tmpFile = sys_get_temp_dir() . '/composer-phpmd-test-' . uniqid() . '.json';
    file_put_contents($tmpFile, json_encode([
        'name' => 'test/app',
        'scripts' => ['md' => 'my-custom-md-command'],
    ]));

    $installer = new PhpMdComposerScriptsInstaller();
    $installer->install($tmpFile);

    $data = json_decode(file_get_contents($tmpFile), true);
    expect($data['scripts']['md'])->toBe('my-custom-md-command');

    unlink($tmpFile);
});

it('preserves existing non-phpmd scripts', function () {
    $tmpFile = sys_get_temp_dir() . '/composer-phpmd-test-' . uniqid() . '.json';
    file_put_contents($tmpFile, json_encode([
        'name' => 'test/app',
        'scripts' => ['test' => 'pest', 'stan' => 'vendor/bin/phpstan analyse'],
    ]));

    $installer = new PhpMdComposerScriptsInstaller();
    $installer->install($tmpFile);

    $data = json_decode(file_get_contents($tmpFile), true);
    expect($data['scripts']['test'])->toBe('pest')
        ->and($data['scripts']['stan'])->toBe('vendor/bin/phpstan analyse');

    unlink($tmpFile);
});

it('returns false when composer.json does not exist', function () {
    $installer = new PhpMdComposerScriptsInstaller();
    expect($installer->install('/nonexistent/composer.json'))->toBeFalse();
});
