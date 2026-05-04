<?php

namespace Kwidoo\HexTools;

use Illuminate\Support\ServiceProvider;
use Kwidoo\HexTools\Commands\CreateAdrCommand;
use Kwidoo\HexTools\Commands\GenerateDocsCommand;
use Kwidoo\HexTools\Commands\GenerateLayersDeptracCommand;
use Kwidoo\HexTools\Commands\GenerateMermaidCommand;
use Kwidoo\HexTools\Commands\GenerateModulesDeptracCommand;
use Kwidoo\HexTools\Commands\HexMapCommand;
use Kwidoo\HexTools\Commands\InitModuleCommand;
use Kwidoo\HexTools\Commands\InstallHexToolsCommand;
use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Generators\AdrGenerator;
use Kwidoo\HexTools\Generators\ComposerScriptsInstaller;
use Kwidoo\HexTools\Generators\DeptracLayersGenerator;
use Kwidoo\HexTools\Generators\DeptracModulesGenerator;
use Kwidoo\HexTools\Generators\MermaidLayerGraphGenerator;
use Kwidoo\HexTools\Generators\MermaidModuleGraphGenerator;
use Kwidoo\HexTools\Generators\ModuleDocsGenerator;
use Kwidoo\HexTools\Scanners\ClassNameResolver;
use Kwidoo\HexTools\Scanners\ModuleScanner;
use Kwidoo\HexTools\Support\Filesystem;
use Kwidoo\HexTools\Support\StubRenderer;

class HexToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/hex-tools.php', 'hex-tools');

        $this->app->singleton(HexToolsConfig::class, function ($app) {
            return new HexToolsConfig($app['config']->get('hex-tools', []));
        });

        $this->app->singleton(Filesystem::class);
        $this->app->singleton(StubRenderer::class);
        $this->app->singleton(ComposerScriptsInstaller::class);

        $this->app->singleton(ClassNameResolver::class, function ($app) {
            $config = $app->make(HexToolsConfig::class);
            return new ClassNameResolver(
                $config->get('paths.app', base_path('app')),
                $config->namespace()
            );
        });

        $this->app->singleton(ModuleScanner::class, function ($app) {
            return new ModuleScanner(
                $app->make(HexToolsConfig::class),
                $app->make(Filesystem::class),
                $app->make(ClassNameResolver::class)
            );
        });

        $this->app->singleton(DeptracLayersGenerator::class, function ($app) {
            return new DeptracLayersGenerator($app->make(HexToolsConfig::class));
        });

        $this->app->singleton(DeptracModulesGenerator::class, function ($app) {
            return new DeptracModulesGenerator($app->make(HexToolsConfig::class));
        });

        $this->app->singleton(ModuleDocsGenerator::class, function ($app) {
            return new ModuleDocsGenerator(
                $app->make(HexToolsConfig::class),
                $app->make(StubRenderer::class)
            );
        });

        $this->app->singleton(AdrGenerator::class, function ($app) {
            return new AdrGenerator(
                $app->make(HexToolsConfig::class),
                $app->make(StubRenderer::class)
            );
        });

        $this->app->singleton(MermaidModuleGraphGenerator::class, function ($app) {
            return new MermaidModuleGraphGenerator(
                $app->make(HexToolsConfig::class),
                $app->make(StubRenderer::class)
            );
        });

        $this->app->singleton(MermaidLayerGraphGenerator::class, function ($app) {
            return new MermaidLayerGraphGenerator(
                $app->make(HexToolsConfig::class),
                $app->make(StubRenderer::class)
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/hex-tools.php' => config_path('hex-tools.php'),
            ], 'hex-tools-config');

            $this->commands([
                InstallHexToolsCommand::class,
                GenerateLayersDeptracCommand::class,
                GenerateModulesDeptracCommand::class,
                HexMapCommand::class,
                InitModuleCommand::class,
                GenerateDocsCommand::class,
                GenerateMermaidCommand::class,
                CreateAdrCommand::class,
            ]);
        }
    }
}
