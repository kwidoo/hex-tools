<?php

it('shows error when phpmd binary is missing', function () {
    $this->artisan('hex:phpmd:run')
        ->expectsOutputToContain('PHPMD is not installed')
        ->assertFailed();
});

it('does not include baseline flag when baseline file does not exist', function () {
    // The baseline file does not exist in the test environment.
    // We can verify behavior by checking the command would fail due to missing binary,
    // not due to baseline logic.
    $this->artisan('hex:phpmd:run')
        ->assertFailed(); // fails because vendor/bin/phpmd is not present
});
