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
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\TableForm::class => Form\TableForm::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
            Controller\Admin\TableController::class => Controller\Admin\TableController::class,
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'table' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/table[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Table\Controller\Admin',
                                'controller' => Controller\Admin\TableController::class,
                                'action' => 'browse',
                            ],
                        ],
                    ],
                    'table-id' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            // TODO Exclude common action as slug (add, edit, browse, show, delete, etc.). So action will be skippable.
                            'route' => '/table/:slug/:action',
                            'constraints' => [
                                'slug' => '\d+|[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Table\Controller\Admin',
                                'controller' => Controller\Admin\TableController::class,
                                // 'action' => 'show',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'column_defaults' => [
        'admin' => [
            'tables' => [
                ['type' => 'owner'],
                ['type' => 'slug'],
                ['type' => 'created'],
            ],
        ],
    ],
    'browse_defaults' => [
        'admin' => [
            'tables' => [
                'sort_by' => 'title',
                'sort_order' => 'asc',
            ],
        ],
    ],
    'sort_defaults' => [
        'admin' => [
            'tables' => [
                'title' => 'Title', // @translate
                'owner_name' => 'Owner', // @translate
                'created' => 'Created', // @translate
                'slug' => 'Slug', // @translate
                'element_count' => 'Element count', // @translate
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            'table' => [
                'label' => 'Tables', // @translate
                'route' => 'admin/table',
                'resource' => Controller\Admin\TableController::class,
                'privilege' => 'browse',
                'class' => 'o-icon- fa-table',
            ],
        ],
    ],
    'table' => [
    ],
];
