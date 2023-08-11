<?php

return [
    'plugin'   => [
        'name'        => 'Breadcrums',
        'description' => 'Render breadcrumbs on page',
        'permissions' => [
            'access_settings' => 'Access breadcrumbs configuration settings',
        ],
    ],
    'settings' => [
        'label'       => 'Breadcrums',
        'description' => 'Settings breadcrumbs',
        'homepage'    => 'Home page',
        'catalog'     => 'Catalog page',
        'no_select'   => '-- No select --',
    ],
    'component' => [
        'name'        => 'Breadcrums',
        'description' => 'Render breadcrumbs on page',
    ],
    'exceptions' => [
        'duplicate' => 'Breadcrumb name :name has already been registered',
        'invalid'   => 'Breadcrumb not found with name :name',
    ],
];
