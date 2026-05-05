<?php

namespace Kwidoo\HexTools;

use Illuminate\Support\ServiceProvider;
use Kwidoo\HexTools\Application\Analysis\ArchitectureDoctor;
use Kwidoo\HexTools\Application\Analysis\ArchitectureDocumentationGenerator;
use Kwidoo\HexTools\Application\Analysis\ArchitectureInspector;
use Kwidoo\HexTools\Application\Analysis\BaselineRepository;
use Kwidoo\HexTools\Application\Analysis\ExplanationProvider;
use Kwidoo\HexTools\Application\Reports\ArchitectureReportFormatter;
use Kwidoo\HexTools\Commands\BaselineCommand;
use Kwidoo\HexTools\Commands\CreateAdrCommand;
use Kwidoo\HexTools\Commands\DoctorCommand;
use Kwidoo\HexTools\Commands\DocsCommand;
use Kwidoo\HexTools\Commands\ExplainCommand;
use Kwidoo\HexTools\Commands\GenerateDocsCommand;
use Kwidoo\HexTools\Commands\GenerateLayersDeptracCommand;
use Kwidoo\HexTools\Commands\GenerateMermaidCommand;
use Kwidoo\HexTools\Commands\GenerateModulesDeptracCommand;
use Kwidoo\HexTools\Commands\GeneratePhpMdBaselineCommand;
use Kwidoo\HexTools\Commands\GeneratePhpMdCommand;
use Kwidoo\HexTools\Commands\GeneratePhpStanBaselineCommand;
use Kwidoo\HexTools\Commands\GeneratePhpStanCommand;
use Kwidoo\HexTools\Commands\HexMapCommand;
use Kwidoo\HexTools\Commands\InstallPhpMdCommand;
use Kwidoo\HexTools\Commands\RunPhpMdCommand;
use Kwidoo\HexTools\Commands\InitModuleCommand;
use Kwidoo\HexTools\Commands\InstallHexToolsCommand;
use Kwidoo\HexTools\Commands\InstallPhpStanCommand;
use Kwidoo\HexTools\Commands\InspectCommand;
use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Generators\AdrGenerator;
use Kwidoo\HexTools\Generators\ComposerScriptsInstaller;
use Kwidoo\HexTools\Generators\DeptracLayersGenerator;
use Kwidoo\HexTools\Generators\DeptracModulesGenerator;
use Kwidoo\HexTools\Generators\MermaidLayerGraphGenerator;
use Kwidoo\HexTools\Generators\MermaidModuleGraphGenerator;
use Kwidoo\HexTools\Generators\ModuleDocsGenerator;
use Kwidoo\HexTools\Generators\PhpMdComposerScriptsInstaller;
use Kwidoo\HexTools\Generators\PhpMdDocsGenerator;
use Kwidoo\HexTools\Generators\PhpMdRulesetGenerator;
use Kwidoo\HexTools\Generators\PhpStanComposerScriptsInstaller;
use Kwidoo\HexTools\Generators\PhpStanConfigGenerator;
use Kwidoo\HexTools\Generators\StaticAnalysisDocsGenerator;
use Kwidoo\HexTools\Infrastructure\Filesystem\SourceFileScanner;
use Kwidoo\HexTools\Scanners\ClassNameResolver;
use Kwidoo\HexTools\Scanners\ModuleScanner;
use Kwidoo\HexTools\Support\Filesystem;
use Kwidoo\HexTools\Support\ProcessRunner;
use Kwidoo\HexTools\Support\StubRenderer;
use Kwidoo\HexTools\Support\ToolAvailability;

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
        $this->app->singleton(ToolAvailability::class);
        $this->app->singleton(ProcessRunner::class);
        $this->app->singleton(ComposerScriptsInstaller::class);
        $this->app->singleton(PhpStanComposerScriptsInstaller::class);
        $this->app->singleton(PhpMdComposerScriptsInstaller::class);
        $this->app->singleton(SourceFileScanner::class);
        $this->app->singleton(BaselineRepository::class);
        $this->app->singleton(ArchitectureInspector::class);
        $this->app->singleton(ArchitectureDoctor::class);
        $this->app->singleton(ArchitectureDocumentationGenerator::class);
        $this->app->singleton(ExplanationProvider::class);
        $this->app->singleton(ArchitectureReportFormatter::class);

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

        $this->app->singleton(PhpStanConfigGenerator::class, function ($app) {
            return new PhpStanConfigGenerator(
                $app->make(HexToolsConfig::class),
                $app->make(StubRenderer::class)
            );
        });

        $this->app->singleton(StaticAnalysisDocsGenerator::class, function ($app) {
            return new StaticAnalysisDocsGenerator(
                $app->make(HexToolsConfig::class),
                $app->make(StubRenderer::class)
            );
        });

        $this->app->singleton(PhpMdRulesetGenerator::class, function ($app) {
            return new PhpMdRulesetGenerator(
                $app->make(HexToolsConfig::class),
                $app->make(StubRenderer::class)
            );
        });

        $this->app->singleton(PhpMdDocsGenerator::class, function ($app) {
            return new PhpMdDocsGenerator(
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
                InstallPhpStanCommand::class,
                GeneratePhpStanCommand::class,
                GeneratePhpStanBaselineCommand::class,
                InstallPhpMdCommand::class,
                GeneratePhpMdCommand::class,
                GeneratePhpMdBaselineCommand::class,
                RunPhpMdCommand::class,
                InspectCommand::class,
                DoctorCommand::class,
                DocsCommand::class,
                BaselineCommand::class,
                ExplainCommand::class,
            ]);
        }
    }
}
