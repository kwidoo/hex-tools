<?php

it('prints baseline command by default', function () {
    $this->artisan('hex:phpmd:baseline')
        ->expectsOutputToContain('vendor/bin/phpmd app text phpmd.xml --generate-baseline')
        ->assertSuccessful();
});

it('prints custom path in the baseline command', function () {
    $this->artisan('hex:phpmd:baseline', ['--path' => 'app/Domain'])
        ->expectsOutputToContain('app/Domain')
        ->assertSuccessful();
});

it('prints custom ruleset in the baseline command', function () {
    $this->artisan('hex:phpmd:baseline', ['--ruleset' => 'custom-rules.xml'])
        ->expectsOutputToContain('custom-rules.xml')
        ->assertSuccessful();
});

it('prints custom baseline file in the command', function () {
    $this->artisan('hex:phpmd:baseline', ['--baseline' => 'my-baseline.xml'])
        ->expectsOutputToContain('--baseline-file my-baseline.xml')
        ->assertSuccessful();
});

it('shows error when phpmd binary is missing and --run is passed', function () {
    $this->artisan('hex:phpmd:baseline', ['--run' => true])
        ->expectsOutputToContain('vendor/bin/phpmd not found')
        ->assertFailed();
});
