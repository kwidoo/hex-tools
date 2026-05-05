# Task: Implement Architecture Intelligence Layer for `kwidoo/hex-tools`

## Context

The package `kwidoo/hex-tools` already contains the initial tooling for Laravel/PHP hexagonal architecture:

- Artisan command foundation
- Layer/module detection
- Layer generator
- README/templates generation
- Composer scripts integration
- PHPStan/Larastan support
- Deptrac support
- PHPMD support
- Quality tooling presets

This task must build on top of the existing implementation.

Do not duplicate already implemented commands or rewrite the package architecture from scratch.

The goal is to add an "Architecture Intelligence Layer" that can inspect, diagnose, document, baseline, and explain architecture issues in a Laravel/PHP project.

---

## Goal

Implement the following new commands:

```bash
php artisan hex:inspect
php artisan hex:doctor
php artisan hex:docs
php artisan hex:baseline
php artisan hex:explain
````

These commands should reuse the existing module/layer detection logic and existing configuration conventions.

The package should evolve from "generator + quality config helper" into a practical architecture toolkit:

```text
Generate
Inspect
Diagnose
Document
Baseline
Explain
```

---

## Strict Scope

### Allowed

* Add new console commands.
* Add services, DTOs, contracts, analyzers, reporters, and formatters needed for these commands.
* Reuse existing package configuration.
* Add tests for new behavior.
* Add/update package documentation.
* Add small internal helpers if needed.

### Not Allowed

* Do not rewrite existing generators.
* Do not remove existing commands.
* Do not change public APIs unless strictly necessary.
* Do not introduce runtime framework magic.
* Do not implement automatic source-code refactoring.
* Do not add AI/LLM features.
* Do not force one single project structure.
* Do not make the package depend on a specific application namespace only.
* Do not modify unrelated application code.

---

## Preferred Internal Structure

Follow the existing package style, but prefer a clean internal architecture similar to:

```text
src/
  Console/
    Commands/
      InspectCommand.php
      DoctorCommand.php
      DocsCommand.php
      BaselineCommand.php
      ExplainCommand.php

  Domain/
    Architecture/
      Module.php
      Layer.php
      DependencyViolation.php
      ArchitectureIssue.php
      ArchitectureReport.php
      ArchitectureScore.php

  Application/
    Analysis/
      ArchitectureInspector.php
      ArchitectureDoctor.php
      BaselineGenerator.php
      DocumentationGenerator.php
      ExplanationProvider.php

    Reports/
      ReportFormatter.php
      MarkdownReportFormatter.php
      JsonReportFormatter.php
      ConsoleReportFormatter.php

  Infrastructure/
    Filesystem/
      SourceFileScanner.php
      ComposerJsonUpdater.php
      MarkdownFileWriter.php
      BaselineFileWriter.php

  Support/
    Config/
    Paths/
    Stubs/
```

Adapt names to existing package conventions if they already exist.

---

# Part 1: `hex:inspect`

## Command

```bash
php artisan hex:inspect
```

Optional flags:

```bash
php artisan hex:inspect --format=table
php artisan hex:inspect --format=json
php artisan hex:inspect --format=md
php artisan hex:inspect --module=Product
php artisan hex:inspect --fail-on-violations
```

## Responsibilities

The command should inspect detected modules and layers and produce an architecture report.

It should detect at minimum:

1. Modules discovered in configured module paths.
2. Layers discovered per module.
3. Missing expected layers.
4. Dependencies between layers.
5. Obvious architecture violations.
6. Missing module README files.
7. Missing tests for modules, if detectable.
8. Unknown/unclassified files.
9. Public API candidates per module.
10. Incoming and outgoing dependencies, where possible.

## Example console output

```text
Hex Architecture Inspection

Module: Product

Detected layers:
  [OK] Domain
  [OK] Application
  [OK] Infrastructure
  [OK] Http

Issues:
  [FAIL] Domain imports Illuminate\Database\Eloquent\Model
  [WARN] Module README.md is missing
  [WARN] ProductRepositoryContract has no implementation detected

Suggestions:
  - Move Eloquent-specific logic to Infrastructure
  - Keep Domain layer framework-independent
  - Add README.md from the module template
```

## JSON Output

When using:

```bash
php artisan hex:inspect --format=json
```

Return a machine-readable structure similar to:

```json
{
  "modules": [
    {
      "name": "Product",
      "path": "app/Modules/Product",
      "layers": ["Domain", "Application", "Infrastructure", "Http"],
      "issues": [
        {
          "severity": "fail",
          "code": "domain_depends_on_framework",
          "message": "Domain imports Illuminate\\Database\\Eloquent\\Model",
          "file": "app/Modules/Product/Domain/Product.php"
        }
      ],
      "suggestions": [
        "Move Eloquent-specific logic to Infrastructure"
      ]
    }
  ],
  "summary": {
    "modules": 1,
    "issues": 1,
    "warnings": 1,
    "score": 82
  }
}
```

---

# Part 2: `hex:doctor`

## Command

```bash
php artisan hex:doctor
```

Optional flags:

```bash
php artisan hex:doctor --format=table
php artisan hex:doctor --format=json
php artisan hex:doctor --strict
```

## Responsibilities

The command should diagnose whether the project is properly configured for `hex-tools`.

Check at minimum:

```text
[OK] hex-tools config exists
[OK] module paths are configured
[OK] deptrac config exists
[OK] phpstan config exists
[OK] phpmd config exists
[OK] pint config exists
[WARN] rector config missing
[WARN] CI workflow missing
[FAIL] configured module path does not exist
[FAIL] composer scripts are missing
```

## Difference between `inspect` and `doctor`

* `hex:inspect` analyzes architecture and source code structure.
* `hex:doctor` checks project setup, configuration, and tooling health.

Do not mix them too much.

---

# Part 3: `hex:docs`

## Command

```bash
php artisan hex:docs
```

Optional flags:

```bash
php artisan hex:docs --output=docs/architecture
php artisan hex:docs --format=md
php artisan hex:docs --module=Product
php artisan hex:docs --force
```

## Responsibilities

Generate architecture documentation based on detected modules, layers, and inspection reports.

Create files like:

```text
docs/architecture/index.md
docs/architecture/modules.md
docs/architecture/layers.md
docs/architecture/dependencies.md
docs/architecture/violations.md
docs/architecture/baseline.md
```

If `--module=Product` is provided, generate only module-specific documentation.

## Example generated module section

```md
# Product Module

## Layers

- Domain
- Application
- Infrastructure
- Http

## Public API Candidates

- App\Modules\Product\Application\UseCases\CreateProductUseCase
- App\Modules\Product\Application\UseCases\UpdateProductUseCase

## Incoming Dependencies

- App\Http\Controllers\ProductController

## Outgoing Dependencies

- App\Modules\Product\Application\Contracts\ProductRepositoryContract

## Architecture Issues

| Severity | Code | Message |
|---|---|---|
| fail | domain_depends_on_framework | Domain imports Illuminate\Database\Eloquent\Model |

## Suggestions

- Move Eloquent-specific logic to Infrastructure.
- Add README.md for the module.
```

## Important

Generated docs must not overwrite manually written docs unless `--force` is provided.

If a file already exists and `--force` is not passed, either skip it or write a clearly named generated file.

---

# Part 4: `hex:baseline`

## Command

```bash
php artisan hex:baseline
```

Optional flags:

```bash
php artisan hex:baseline --output=.hex/baseline/architecture.json
php artisan hex:baseline --format=json
php artisan hex:baseline --update
```

## Responsibilities

Create a baseline of current architecture violations.

The purpose is to support legacy projects where existing violations are accepted temporarily, but new violations should still be detected.

Create a file similar to:

```text
.hex/baseline/architecture.json
```

Example:

```json
{
  "generatedAt": "2026-05-04T00:00:00+00:00",
  "issues": [
    {
      "code": "domain_depends_on_framework",
      "file": "app/Modules/Product/Domain/Product.php",
      "message": "Domain imports Illuminate\\Database\\Eloquent\\Model",
      "hash": "stable-issue-hash"
    }
  ]
}
```

## Stable Hash Requirement

Each issue should have a stable hash generated from:

```text
issue code
file path
dependency / symbol / class name if available
```

The hash must not depend on line numbers only, because line numbers change too often.

## Integration with `hex:inspect`

When a baseline exists, `hex:inspect` should be able to distinguish:

```text
Existing baseline issue
New issue
Resolved issue
```

Optional flag:

```bash
php artisan hex:inspect --ignore-baseline
```

---

# Part 5: `hex:explain`

## Command

```bash
php artisan hex:explain <rule-or-code>
```

Examples:

```bash
php artisan hex:explain domain_depends_on_infrastructure
php artisan hex:explain domain_depends_on_framework
php artisan hex:explain application_depends_on_http
php artisan hex:explain missing_module_readme
```

## Responsibilities

Explain architecture rules in practical language.

Example output:

```text
Rule: domain_depends_on_infrastructure

Why this matters:
Domain code should describe business rules. If it depends on Infrastructure, then business logic becomes coupled to databases, HTTP clients, queues, frameworks, or external providers.

Bad example:
Domain\Order\OrderService -> Infrastructure\Database\EloquentOrderRepository

Better approach:
Define OrderRepositoryContract in Application or Domain boundary.
Implement it in Infrastructure using EloquentOrderRepository.

Suggested structure:
Application\Contracts\OrderRepositoryContract
Infrastructure\Repositories\EloquentOrderRepository
Application\UseCases\CreateOrderUseCase
```

## Required Built-in Explanations

Add explanations for at least:

```text
domain_depends_on_infrastructure
domain_depends_on_framework
domain_depends_on_http
application_depends_on_http
application_depends_on_config
infrastructure_depends_on_http
missing_module_readme
missing_contract_implementation
unknown_layer
unclassified_file
```

The explanation provider should be extendable/configurable later.

---

# Architecture Rules

Implement detection for at least these rule codes:

```text
domain_depends_on_infrastructure
domain_depends_on_framework
domain_depends_on_http
application_depends_on_http
application_depends_on_config
infrastructure_depends_on_http
missing_module_readme
missing_contract_implementation
unknown_layer
unclassified_file
```

The first implementation can be conservative.

It is better to detect fewer issues correctly than many issues incorrectly.

---

# Configuration

Extend the existing config file if needed.

Example:

```php
return [
    'paths' => [
        'modules' => [
            app_path('Modules'),
            app_path('Application'),
        ],
    ],

    'layers' => [
        'domain' => [
            'name' => 'Domain',
            'paths' => ['Domain'],
            'forbidden_dependencies' => [
                'Illuminate\\Database',
                'Illuminate\\Http',
                'App\\Infrastructure',
            ],
        ],

        'application' => [
            'name' => 'Application',
            'paths' => ['Application', 'UseCases'],
            'forbidden_dependencies' => [
                'Illuminate\\Http',
            ],
        ],

        'infrastructure' => [
            'name' => 'Infrastructure',
            'paths' => ['Infrastructure'],
        ],

        'http' => [
            'name' => 'Http',
            'paths' => ['Http', 'Controllers', 'Requests'],
        ],
    ],

    'docs' => [
        'output_path' => base_path('docs/architecture'),
    ],

    'baseline' => [
        'path' => base_path('.hex/baseline/architecture.json'),
    ],
];
```

Do not hardcode one exact app structure.

---

# Output Formats

Support at least:

```text
console/table
json
markdown
```

Do not make the console output the only source of truth.

The internal report object should be format-independent.

---

# Tests

Add tests for all new commands.

At minimum:

## `hex:inspect`

* Detects modules.
* Detects layers.
* Detects missing README.
* Detects forbidden dependency.
* Supports JSON output.
* Supports module filter.
* Supports `--fail-on-violations`.

## `hex:doctor`

* Detects existing config files.
* Reports missing quality config.
* Reports missing composer scripts.
* Supports JSON output.
* Supports strict mode.

## `hex:docs`

* Generates markdown docs.
* Does not overwrite existing files without `--force`.
* Generates module-specific docs with `--module`.

## `hex:baseline`

* Creates baseline file.
* Generates stable issue hashes.
* Existing baseline issues are recognized by inspect.
* New issues are still reported.

## `hex:explain`

* Explains known rule codes.
* Returns useful message for unknown rule code.
* Has tests for at least 3 built-in explanations.

---

# Documentation

Update package documentation with:

```text
README.md
docs/commands/inspect.md
docs/commands/doctor.md
docs/commands/docs.md
docs/commands/baseline.md
docs/commands/explain.md
docs/architecture-rules.md
```

README should include a short positioning statement:

```md
`kwidoo/hex-tools` is a pragmatic Laravel/PHP toolkit for generating, inspecting, documenting, and enforcing hexagonal architecture boundaries.
```

---

# Acceptance Criteria

The task is complete when:

1. `php artisan hex:inspect` works and produces a useful architecture report.
2. `php artisan hex:doctor` checks package/tooling configuration.
3. `php artisan hex:docs` generates architecture markdown documentation.
4. `php artisan hex:baseline` creates a stable baseline of current violations.
5. `php artisan hex:explain <rule>` explains known architecture rules.
6. All new commands have tests.
7. Reports are internally represented as DTOs or value objects, not just printed directly.
8. JSON output is available for automation/CI usage.
9. Existing commands and generators continue to work.
10. The implementation remains configurable and does not assume only one Laravel structure.

---

# Guard Rails for Coding Agent

* Reuse existing services and configuration where possible.
* Keep changes inside the package.
* Do not refactor unrelated existing code.
* Do not introduce LLM/AI runtime dependencies.
* Do not implement source-code rewriting.
* Do not make the tool too magical.
* Prefer explicit reports, clear rule codes, and stable machine-readable output.
* Keep the first implementation conservative and reliable.

````

И короткий prompt для Copilot:

```md
Read the task file and implement the Architecture Intelligence Layer for `kwidoo/hex-tools`.

Build on the existing commands, generators, layer detection, module detection, and quality-tooling setup. Do not rewrite existing functionality.

Implement the new commands:

- `hex:inspect`
- `hex:doctor`
- `hex:docs`
- `hex:baseline`
- `hex:explain`

Follow the strict scope, guard rails, tests, and acceptance criteria from the task file.
