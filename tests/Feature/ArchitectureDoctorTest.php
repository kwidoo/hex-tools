<?php

it('reports missing quality tooling and composer scripts', function () {
    $this->artisan('hex:doctor')
        ->assertExitCode(1)
        ->expectsOutputToContain('Hex Tools Doctor')
        ->expectsOutputToContain('phpstan config')
        ->expectsOutputToContain('composer scripts are missing');
});

it('supports json output and strict mode', function () {
    $this->artisan('hex:doctor', ['--format' => 'json', '--strict' => true])
        ->assertExitCode(1)
        ->expectsOutputToContain('"checks"')
        ->expectsOutputToContain('"summary"');
});
