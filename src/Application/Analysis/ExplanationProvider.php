<?php

namespace Kwidoo\HexTools\Application\Analysis;

use Kwidoo\HexTools\Domain\Architecture\RuleExplanation;

class ExplanationProvider
{
    public function explain(string $code): ?RuleExplanation
    {
        $rules = $this->rules();

        return $rules[$code] ?? null;
    }

    /** @return array<string, RuleExplanation> */
    protected function rules(): array
    {
        $data = [
            'domain_depends_on_infrastructure' => [
                'Domain code should describe business rules. If it depends on Infrastructure, persistence, HTTP clients, queues, and providers leak into the model.',
                'Domain\\Order\\OrderService -> Infrastructure\\Repositories\\EloquentOrderRepository',
                'Define a repository contract at the boundary and implement it in Infrastructure.',
                "Application\\Contracts\\OrderRepositoryContract\nInfrastructure\\Repositories\\EloquentOrderRepository\nApplication\\UseCases\\CreateOrderUseCase",
            ],
            'domain_depends_on_framework' => [
                'Domain should be portable business code. Framework imports make entities and policies harder to test and reuse.',
                'Domain\\Product\\Product extends Illuminate\\Database\\Eloquent\\Model',
                'Keep Eloquent models in Models or Infrastructure and map them to domain objects.',
                "Domain\\Product\\Product\nInfrastructure\\Persistence\\EloquentProductRepository\nModels\\ProductModel",
            ],
            'domain_depends_on_http' => [
                'HTTP is a delivery mechanism. Domain rules should not know about requests, controllers, or resources.',
                'Domain\\Order\\OrderFactory accepts Illuminate\\Http\\Request',
                'Convert HTTP input into commands or DTOs before calling Domain/Application code.',
                "Http\\Controllers\\OrderController\nApplication\\Commands\\CreateOrderCommand\nDomain\\Order\\Order",
            ],
            'application_depends_on_http' => [
                'Application services should orchestrate use cases without depending on web transport details.',
                'Application\\UseCases\\CreateProductUseCase accepts StoreProductRequest',
                'Pass simple DTOs, command objects, or scalar values into the use case.',
                "Http\\Requests\\StoreProductRequest\nApplication\\Commands\\CreateProductCommand\nApplication\\UseCases\\CreateProductUseCase",
            ],
            'application_depends_on_config' => [
                'Configuration is framework state. Reading it directly hides dependencies and complicates testing.',
                'Application\\Pricing\\PriceCalculator calls config("pricing.tax")',
                'Read config at the edge and inject typed values or settings objects.',
                "Infrastructure\\Config\\PricingSettingsFactory\nApplication\\Pricing\\PricingSettings",
            ],
            'infrastructure_depends_on_http' => [
                'Infrastructure should implement technical adapters, not controller/request/resource behavior.',
                'Infrastructure\\Sync\\RemoteProductClient imports Http\\Resources\\ProductResource',
                'Keep HTTP presentation in Http and use Infrastructure for gateways, persistence, and providers.',
                "Http\\Resources\\ProductResource\nInfrastructure\\Clients\\RemoteProductClient",
            ],
            'missing_module_readme' => [
                'A module README records ownership, boundaries, and intended responsibilities for future maintainers.',
                'A Product module exists with no local README or generated module documentation.',
                'Add a concise README describing purpose, layers, public APIs, and dependencies.',
                "app\\Domain\\Product\\README.md\ndocs\\architecture\\modules\\product.md",
            ],
            'missing_contract_implementation' => [
                'Contracts that are never implemented usually indicate dead abstractions or incomplete wiring.',
                'ProductRepositoryContract exists but no class implements it.',
                'Add an implementation or remove the unused contract.',
                "Application\\Contracts\\ProductRepositoryContract\nInfrastructure\\Repositories\\EloquentProductRepository",
            ],
            'unknown_layer' => [
                'Unknown layers cannot be checked reliably because the tool does not know their dependency rules.',
                'app\\Adapters\\Product\\SomeClass.php is not configured as a layer.',
                'Move the file to a known layer or add the layer to hex-tools configuration.',
                "config\\hex-tools.php layers\napp\\Infrastructure\\Product",
            ],
            'unclassified_file' => [
                'Unclassified files are outside the configured module collectors and may bypass architecture checks.',
                'ProductExport.php is under app\\Services without a configured module layer.',
                'Move the file into a module layer or update collectors/paths.',
                "app\\Application\\Product\\ExportProductUseCase\nconfig\\hex-tools.php module_collectors",
            ],
        ];

        $rules = [];
        foreach ($data as $code => [$why, $bad, $better, $structure]) {
            $rules[$code] = new RuleExplanation($code, $why, $bad, $better, $structure);
        }

        return $rules;
    }
}
