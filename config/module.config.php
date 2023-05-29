<?php declare(strict_types=1);

namespace Table;

return [
    'api_adapters' => [
        'invokables' => [
            'tables' => Api\Adapter\TableAdapter::class,
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
    ],
    'table' => [
    ],
];
