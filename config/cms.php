<?php

return [
    'name' => env('CMS_NAME', 'Laravel CMS V4'),
    'version' => '6.0.0',

    // V6: Dual ID support (uuid_v7 | uuid_v4 | bigint)
    'id_type' => env('CMS_ID_TYPE', 'uuid_v7'),
    'machine_id' => env('CMS_MACHINE_ID', 1),  // 0-1023 for snowflake bigint

    'central_domain' => env('APP_CENTRAL_DOMAIN', 'platform.test'),

    'default_theme' => 'foundation',
    'default_locale' => env('CMS_DEFAULT_LOCALE', 'en'),
    'default_timezone' => env('CMS_DEFAULT_TIMEZONE', 'UTC'),

    'cache' => [
        'enabled' => env('CMS_CACHE_ENABLED', true),
        'ttl_seconds' => env('CMS_CACHE_TTL', 300),
        'full_page_cache' => env('CMS_FULL_PAGE_CACHE', true),
    ],

    'upload' => [
        'max_file_size_mb' => env('CMS_UPLOAD_MAX_MB', 50),
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        'allowed_file_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'txt'],
    ],

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

    'feature_flags' => [
        'multi_domain' => env('CMS_FEATURE_MULTI_DOMAIN', true),
        'connector' => env('CMS_FEATURE_CONNECTOR', true),
        'workflow_engine' => env('CMS_FEATURE_WORKFLOW', true),
        'ab_testing' => env('CMS_FEATURE_AB_TESTING', true),
        'collab_editing' => env('CMS_FEATURE_COLLAB', true),
        'ai_rag' => env('CMS_FEATURE_AI_RAG', true),
        'personalization' => env('CMS_FEATURE_PERSONALIZATION', true),
        'saml_sso' => env('CMS_FEATURE_SAML_SSO', true),
        'scim_provisioning' => env('CMS_FEATURE_SCIM', true),
        'audit_streaming' => env('CMS_FEATURE_AUDIT_STREAMING', true),
        'form_analytics' => env('CMS_FEATURE_FORM_ANALYTICS', true),
    ],
];
