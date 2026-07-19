<?php

return [
    'permissions' => [
        // Content
        'view entries', 'create entries', 'edit entries', 'publish entries', 'delete entries',
        'view collections', 'manage collections',
        'view blueprints', 'manage blueprints',
        'view taxonomies', 'manage taxonomies',
        'view globals', 'manage globals',
        'view navigations', 'manage navigations',
        'view forms', 'manage forms', 'view form submissions',
        'manage assets', 'upload assets',

        // Users & Roles
        'view users', 'manage users', 'invite users',
        'view roles', 'manage roles',

        // Domains (V4)
        'manage domains', 'manage ssl certificates', 'manage dns verification',
        'view domain analytics', 'manage domain config',

        // Themes
        'manage themes', 'edit theme files', 'access theme marketplace', 'customize themes',

        // AI Tools
        'use ai tools', 'use rag tools', 'manage rag settings', 'view rag queries log',

        // API
        'access api', 'manage api tokens',

        // Billing
        'view billing', 'manage billing',

        // Connectors (V4)
        'manage connectors', 'view connector logs', 'manage connector webhooks',

        // Workflows (V4)
        'manage workflows', 'approve in workflows', 'view workflow instances', 'cancel workflow instances',

        // Experiments (V4)
        'manage experiments',

        // Personalization (V4)
        'manage segments', 'manage personalization rules',

        // SSO (V4)
        'manage sso', 'manage scim',

        // Audit (V4)
        'manage audit streams', 'view audit log',

        // Forms (V4)
        'view form analytics', 'manage lead scoring', 'view leads', 'assign leads',
    ],

    'default_roles' => [
        'owner' => ['*'], // all permissions
        'admin' => [
            'view entries', 'create entries', 'edit entries', 'publish entries', 'delete entries',
            'view collections', 'manage collections',
            'view blueprints', 'manage blueprints',
            'view taxonomies', 'manage taxonomies',
            'view globals', 'manage globals',
            'view navigations', 'manage navigations',
            'view forms', 'manage forms', 'view form submissions',
            'manage assets', 'upload assets',
            'view users', 'manage users', 'invite users',
            'view roles',
            'manage domains',
            'manage themes', 'customize themes',
            'use ai tools', 'use rag tools',
            'access api', 'manage api tokens',
            'view billing', 'manage billing',
            'manage connectors',
            'manage workflows', 'approve in workflows', 'view workflow instances',
            'manage experiments',
            'manage segments', 'manage personalization rules',
            'view form analytics', 'manage lead scoring', 'view leads', 'assign leads',
        ],
        'editor' => [
            'view entries', 'create entries', 'edit entries', 'publish entries',
            'view collections', 'view blueprints',
            'view taxonomies', 'manage taxonomies',
            'view globals', 'manage globals',
            'view navigations', 'manage navigations',
            'view forms', 'manage forms', 'view form submissions',
            'manage assets', 'upload assets',
            'view users',
            'use ai tools', 'use rag tools',
            'approve in workflows', 'view workflow instances',
            'view form analytics', 'view leads',
        ],
        'author' => [
            'view entries', 'create entries', 'edit entries',
            'view collections', 'view blueprints',
            'view taxonomies',
            'view globals',
            'view navigations',
            'view forms',
            'manage assets', 'upload assets',
            'use ai tools',
            'approve in workflows',
        ],
        'contributor' => [
            'view entries', 'create entries',
            'view collections',
            'view blueprints',
            'upload assets',
        ],
        'viewer' => [
            'view entries', 'view collections', 'view blueprints',
            'view taxonomies', 'view globals', 'view navigations', 'view forms',
        ],
    ],
];
