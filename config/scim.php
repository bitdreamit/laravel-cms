<?php

return [
    'enabled' => env('SCIM_ENABLED', false),

    'routes' => [
        'prefix' => 'scim/v2',
        'middleware' => ['api', 'scim-auth'],
    ],

    'user' => [
        'model' => \App\Models\Central\User::class,
        'mapping' => [
            'userName' => 'email',
            'displayName' => 'name',
            'active' => 'is_active',
            'emails' => [
                'type' => 'emails',
                'value' => 'email',
                'primary' => null,
            ],
        ],
        'deactivate_on_delete' => true, // soft delete vs hard delete
    ],

    'group' => [
        'model' => \Spatie\Permission\Models\Role::class,
        'mapping' => [
            'displayName' => 'name',
        ],
        'team_scope' => 'tenant_id', // spatie/laravel-permission team_id column
    ],

    'pagination' => [
        'default_count' => 100,
        'max_count' => 1000,
    ],

    'filter' => [
        'enabled' => true,
        'parser' => \Tmilos\ScimFilterParser\Parser::class,
    ],
];
