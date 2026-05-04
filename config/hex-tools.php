<?php

return [
    'namespace' => 'App',

    'paths' => [
        'app' => base_path('app'),
        'docs' => base_path('docs/architecture'),
        'adr' => base_path('docs/adr'),
        'build' => base_path('build/architecture'),
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
