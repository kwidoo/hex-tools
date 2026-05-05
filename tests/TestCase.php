<?php

namespace Kwidoo\HexTools\Tests;

use Kwidoo\HexTools\HexToolsServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [HexToolsServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('hex-tools.namespace', 'App');
        $app['config']->set('hex-tools.paths', [
            'app' => $app->basePath('app'),
            'docs' => $app->basePath('docs/architecture'),
            'adr' => $app->basePath('docs/adr'),
            'build' => $app->basePath('build/architecture'),
        ]);
        $app['config']->set('hex-tools.modules', ['Example', 'Shared']);
        $app['config']->set('hex-tools.layers', [
            'domain' => 'app/Domain',
            'application' => 'app/Application',
            'http' => 'app/Http',
            'infrastructure' => 'app/Infrastructure',
            'models' => 'app/Models',
            'support' => 'app/Support',
            'providers' => 'app/Providers',
            'console' => 'app/Console',
        ]);
        $app['config']->set('hex-tools.module_collectors', [
            'domain' => 'app/Domain/{module}/.*',
            'application' => 'app/Application/{module}/.*',
            'infrastructure' => 'app/Infrastructure/{module}/.*',
            'support' => 'app/Support/{module}/.*',
            'http_controllers' => 'App\\\\Http\\\\Controllers\\\\.*{module}.*',
            'http_requests' => 'App\\\\Http\\\\Requests\\\\.*{module}.*',
            'http_resources' => 'App\\\\Http\\\\Resources\\\\.*{module}.*',
            'models' => 'App\\\\Models\\\\{module}',
        ]);
        $app['config']->set('hex-tools.module_rules', [
            'Shared' => ['Shared'],
            'Example' => ['Example', 'Shared'],
        ]);
    }
}
