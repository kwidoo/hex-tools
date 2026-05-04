<?php

afterEach(function () {
    $adrPath = $this->app->basePath('docs/adr');
    if (is_dir($adrPath)) {
        exec('rm -rf ' . escapeshellarg($adrPath));
    }
});

it('creates an ADR file', function () {
    $this->artisan('hex:adr:create', ['title' => 'Domain must not use Eloquent'])
        ->assertSuccessful();

    $adrPath = $this->app->basePath('docs/adr');
    $files = glob($adrPath . '/*.md');

    expect($files)->toHaveCount(1);
    expect(basename($files[0]))->toStartWith('0001-');
});

it('auto-increments ADR number', function () {
    $this->artisan('hex:adr:create', ['title' => 'First Decision'])->assertSuccessful();
    $this->artisan('hex:adr:create', ['title' => 'Second Decision'])->assertSuccessful();

    $adrPath = $this->app->basePath('docs/adr');
    $files = glob($adrPath . '/*.md');
    $basenames = array_map('basename', $files);

    expect($basenames)->toContain('0001-first-decision.md')
        ->and($basenames)->toContain('0002-second-decision.md');
});

it('sets status in ADR content', function () {
    $this->artisan('hex:adr:create', [
        'title' => 'Some Decision',
        '--status' => 'accepted',
    ])->assertSuccessful();

    $adrPath = $this->app->basePath('docs/adr');
    $files = glob($adrPath . '/*.md');
    $content = file_get_contents($files[0]);

    expect($content)->toContain('Accepted');
});

it('uses proposed status by default', function () {
    $this->artisan('hex:adr:create', ['title' => 'Some Decision'])->assertSuccessful();

    $adrPath = $this->app->basePath('docs/adr');
    $files = glob($adrPath . '/*.md');
    $content = file_get_contents($files[0]);

    expect($content)->toContain('Proposed');
});

it('includes title in ADR content', function () {
    $this->artisan('hex:adr:create', ['title' => 'ProductResource must not query Eloquent'])
        ->assertSuccessful();

    $adrPath = $this->app->basePath('docs/adr');
    $files = glob($adrPath . '/*.md');
    $content = file_get_contents($files[0]);

    expect($content)->toContain('ProductResource must not query Eloquent');
});
