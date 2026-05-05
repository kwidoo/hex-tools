<?php

it('explains known architecture rule codes', function (string $code) {
    $this->artisan('hex:explain', ['rule-or-code' => $code])
        ->assertSuccessful()
        ->expectsOutputToContain("Rule: {$code}")
        ->expectsOutputToContain('Why this matters:')
        ->expectsOutputToContain('Better approach:');
})->with([
    'domain_depends_on_framework',
    'application_depends_on_http',
    'missing_module_readme',
]);

it('returns useful output for unknown rule codes', function () {
    $this->artisan('hex:explain', ['rule-or-code' => 'not_a_rule'])
        ->assertExitCode(1)
        ->expectsOutputToContain('Unknown architecture rule: not_a_rule');
});
