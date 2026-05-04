<?php

it('prints baseline command by default', function () {
    $this->artisan('hex:phpstan:baseline')
        ->expectsOutputToContain('vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --generate-baseline')
        ->assertSuccessful();
});

it('prints custom config in the baseline command', function () {
    $this->artisan('hex:phpstan:baseline', ['--config' => 'phpstan-domain.neon'])
        ->expectsOutputToContain('--configuration=phpstan-domain.neon')
        ->assertSuccessful();
});

it('prints custom memory limit in the baseline command', function () {
    $this->artisan('hex:phpstan:baseline', ['--memory' => '2G'])
        ->expectsOutputToContain('--memory-limit=2G')
        ->assertSuccessful();
});

it('shows error when phpstan binary is missing and --run is passed', function () {
    $this->artisan('hex:phpstan:baseline', ['--run' => true])
        ->expectsOutputToContain('vendor/bin/phpstan not found')
        ->assertFailed();
});
