<?php

namespace Kwidoo\HexTools\Application\Analysis;

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Domain\Architecture\DoctorCheck;
use Kwidoo\HexTools\Domain\Architecture\DoctorReport;

class ArchitectureDoctor
{
    public function __construct(protected HexToolsConfig $config) {}

    public function diagnose(): DoctorReport
    {
        return new DoctorReport([
            $this->checkFile('fail', 'missing_config', 'hex-tools config exists', base_path('config/hex-tools.php'), 'hex-tools config is missing'),
            $this->checkModulePaths(),
            $this->checkAnyFile('warn', 'missing_deptrac_config', 'deptrac config exists', ['deptrac.layers.yaml', 'deptrac.modules.yaml'], 'deptrac config is missing'),
            $this->checkAnyFile('warn', 'missing_phpstan_config', 'phpstan config exists', ['phpstan.neon', 'phpstan.neon.dist'], 'phpstan config is missing'),
            $this->checkFile('warn', 'missing_phpmd_config', 'phpmd config exists', base_path('phpmd.xml'), 'phpmd config is missing'),
            $this->checkAnyFile('warn', 'missing_pint_config', 'pint config exists', ['pint.json', 'pint.php'], 'pint config is missing'),
            $this->checkFile('warn', 'missing_rector_config', 'rector config exists', base_path('rector.php'), 'rector config missing'),
            $this->checkCiWorkflow(),
            $this->checkComposerScripts(),
        ]);
    }

    protected function checkModulePaths(): DoctorCheck
    {
        $paths = [];
        $appPath = $this->config->get('paths.app', base_path('app'));
        foreach ($this->config->layers() as $value) {
            if (is_string($value) && str_contains($value, 'app/')) {
                $paths[] = base_path($value);
            }
        }

        if ($paths === []) {
            $paths = [$appPath];
        }

        foreach (array_unique($paths) as $path) {
            if (!is_dir($path)) {
                return new DoctorCheck('fail', 'configured_module_path_missing', "Configured module path does not exist: {$path}", $path);
            }
        }

        return new DoctorCheck('ok', 'module_paths_configured', 'module paths are configured');
    }

    protected function checkFile(string $missingStatus, string $code, string $okMessage, string $path, string $missingMessage): DoctorCheck
    {
        if (is_file($path)) {
            return new DoctorCheck('ok', $code, $okMessage, $path);
        }

        return new DoctorCheck($missingStatus, $code, $missingMessage, $path);
    }

    /** @param array<string> $paths */
    protected function checkAnyFile(string $missingStatus, string $code, string $okMessage, array $paths, string $missingMessage): DoctorCheck
    {
        foreach ($paths as $path) {
            $absolute = base_path($path);
            if (is_file($absolute)) {
                return new DoctorCheck('ok', $code, $okMessage, $absolute);
            }
        }

        return new DoctorCheck($missingStatus, $code, $missingMessage, base_path($paths[0]));
    }

    protected function checkCiWorkflow(): DoctorCheck
    {
        $workflowDir = base_path('.github/workflows');
        if (is_dir($workflowDir) && glob($workflowDir . '/*.{yml,yaml}', GLOB_BRACE)) {
            return new DoctorCheck('ok', 'ci_workflow', 'CI workflow exists', $workflowDir);
        }

        return new DoctorCheck('warn', 'missing_ci_workflow', 'CI workflow missing', $workflowDir);
    }

    protected function checkComposerScripts(): DoctorCheck
    {
        $path = base_path('composer.json');
        if (!is_file($path)) {
            return new DoctorCheck('fail', 'missing_composer_scripts', 'composer scripts are missing', $path);
        }

        $composer = json_decode(file_get_contents($path), true);
        $scripts = is_array($composer) ? ($composer['scripts'] ?? []) : [];
        $expected = ['hex:layers', 'hex:modules', 'stan', 'md'];

        foreach ($expected as $script) {
            if (array_key_exists($script, $scripts)) {
                return new DoctorCheck('ok', 'composer_scripts', 'composer scripts are configured', $path);
            }
        }

        return new DoctorCheck('fail', 'missing_composer_scripts', 'composer scripts are missing', $path);
    }
}
