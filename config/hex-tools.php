<?php

return [
    'namespace' => 'App',

    'paths' => [
        'app' => base_path('app'),
        'docs' => base_path('docs/architecture'),
        'adr' => base_path('docs/adr'),
        'build' => base_path('build/architecture'),
    ],

    'docs' => [
        'output_path' => base_path('docs/architecture'),
    ],

    'baseline' => [
        'path' => base_path('.hex/baseline/architecture.json'),
    ],

    'architecture' => [
        'expected_layers' => ['Domain', 'Application'],
    ],

    'modules' => [
        'Billing',
        'Category',
        'Farmer',
        'Notification',
        'Offer',
        'Order',
        'Payment',
        'Pickup',
        'Product',
        'Shared',
        'User',
    ],

    'layers' => [
        'domain' => 'app/Domain',
        'application' => 'app/Application',
        'http' => 'app/Http',
        'infrastructure' => 'app/Infrastructure',
        'models' => 'app/Models',
        'support' => 'app/Support',
        'providers' => 'app/Providers',
        'console' => 'app/Console',
    ],

    'module_collectors' => [
        'domain' => 'app/Domain/{module}/.*',
        'application' => 'app/Application/{module}/.*',
        'infrastructure' => 'app/Infrastructure/{module}/.*',
        'support' => 'app/Support/{module}/.*',
        'http_controllers' => 'App\\\\Http\\\\Controllers\\\\.*{module}.*',
        'http_requests' => 'App\\\\Http\\\\Requests\\\\.*{module}.*',
        'http_resources' => 'App\\\\Http\\\\Resources\\\\.*{module}.*',
        'models' => 'App\\\\Models\\\\{module}',
    ],

    'phpmd' => [
        'enabled' => true,

        'paths' => [
            'main' => 'app',
            'domain' => 'app/Domain',
            'application' => 'app/Application',
        ],

        'ruleset' => 'phpmd.xml',
        'baseline' => 'phpmd.baseline.xml',

        'exclude' => [
            'app/Providers',
            'app/Console',
            'database',
            'bootstrap',
            'storage',
            'vendor',
        ],

        'suffixes' => 'php',

        'format' => 'text',

        'rules' => [
            'main' => [
                'codesize',
                'design',
                'naming',
                'unusedcode',
            ],

            'domain' => [
                'codesize',
                'design',
                'naming',
                'unusedcode',
                'cleancode',
            ],

            'application' => [
                'codesize',
                'design',
                'naming',
                'unusedcode',
            ],
        ],

        'thresholds' => [
            'cyclomatic_complexity_report_level' => 10,
            'npath_complexity_report_level' => 200,
            'excessive_method_length_minimum' => 80,
            'excessive_class_length_minimum' => 400,
            'too_many_methods_maxmethods' => 20,
            'too_many_public_methods_maxmethods' => 15,
            'coupling_between_objects_maximum' => 13,
        ],
    ],

    'phpstan' => [
        'enabled' => true,

        'paths' => [
            'main' => ['app'],
            'domain' => ['app/Domain'],
            'application' => ['app/Application'],
        ],

        'levels' => [
            'main' => 5,
            'domain' => 8,
            'application' => 7,
        ],

        'tmp_dirs' => [
            'main' => 'storage/framework/phpstan',
            'domain' => 'storage/framework/phpstan-domain',
            'application' => 'storage/framework/phpstan-application',
        ],

        'exclude_paths' => [
            'database/migrations/*',
            'database/seeders/*',
            'bootstrap/*',
            'storage/*',
        ],

        'includes' => [
            'vendor/larastan/larastan/extension.neon',
            'vendor/nesbot/carbon/extension.neon',
        ],

        'baseline' => 'phpstan-baseline.neon',

        'memory_limit' => '1G',
    ],

    'module_rules' => [
        'Shared' => ['Shared'],
        'User' => ['User', 'Shared'],
        'Farmer' => ['Farmer', 'User', 'Shared'],
        'Category' => ['Category', 'Shared'],
        'Product' => ['Product', 'Category', 'Farmer', 'Shared'],
        'Pickup' => ['Pickup', 'Farmer', 'Shared'],
        'Offer' => ['Offer', 'Product', 'Category', 'Farmer', 'Pickup', 'Shared'],
        'Order' => ['Order', 'Offer', 'Product', 'Category', 'User', 'Farmer', 'Pickup', 'Shared'],
        'Payment' => ['Payment', 'Order', 'User', 'Shared'],
        'Billing' => ['Billing', 'Order', 'Payment', 'User', 'Farmer', 'Shared'],
        'Notification' => ['Notification', 'User', 'Farmer', 'Category', 'Product', 'Pickup', 'Offer', 'Order', 'Payment', 'Billing', 'Shared'],
    ],
];
