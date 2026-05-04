<?php

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Generators\AdrGenerator;
use Kwidoo\HexTools\Support\StubRenderer;

it('returns 1 for first ADR when directory does not exist', function () {
    $config = new HexToolsConfig([
        'paths' => ['adr' => '/nonexistent/path'],
    ]);
    $generator = new AdrGenerator($config, new StubRenderer());

    expect($generator->getNextNumber())->toBe(1);
});

it('increments from existing ADRs', function () {
    $tmpDir = sys_get_temp_dir() . '/adr-test-' . uniqid();
    mkdir($tmpDir);
    file_put_contents($tmpDir . '/0001-first.md', '');
    file_put_contents($tmpDir . '/0003-third.md', '');

    $config = new HexToolsConfig(['paths' => ['adr' => $tmpDir]]);
    $generator = new AdrGenerator($config, new StubRenderer());

    expect($generator->getNextNumber())->toBe(4);

    exec('rm -rf ' . escapeshellarg($tmpDir));
});

it('builds correct filename', function () {
    $config = new HexToolsConfig(['paths' => ['adr' => '/tmp']]);
    $generator = new AdrGenerator($config, new StubRenderer());

    expect($generator->getFilename('Domain must not use Eloquent', 1))
        ->toBe('0001-domain-must-not-use-eloquent.md');
});

it('generates ADR content with correct placeholders', function () {
    $config = new HexToolsConfig(['paths' => ['adr' => '/tmp']]);
    $generator = new AdrGenerator($config, new StubRenderer());

    $content = $generator->generate('My Decision', 'accepted');

    expect($content)->toContain('My Decision')
        ->and($content)->toContain('Accepted');
});
