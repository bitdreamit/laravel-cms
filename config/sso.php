<?php

return [
    'enabled' => env('SAML_ENABLED', false),

    'sp' => [
        'entity_id_format' => '{tenant_domain}/saml/metadata/{idp_id}',
        'name_id_format' => env('SAML_NAME_ID_FORMAT', 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress'),
        'x509_cert_path' => storage_path('app/saml/sp.crt'),
        'private_key_path' => storage_path('app/saml/sp.key'),
        'auto_generate_cert' => true,
    ],

    'security' => [
        'want_messages_signed' => env('SAML_WANT_SIGNED', false),
        'want_assertions_signed' => env('SAML_WANT_ASSERTIONS_SIGNED', true),
        'want_assertions_encrypted' => false,
        'signature_algorithm' => env('SAML_SIG_ALG', 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256'),
        'digest_algorithm' => env('SAML_DIGEST_ALG', 'http://www.w3.org/2001/04/xmlenc#sha256'),
    ],

    'session' => [
        'ttl_minutes' => 5,
        'cleanup_minutes' => 60,
    ],

    'auto_create_users' => env('SAML_AUTO_CREATE', true),
    'default_role_on_create' => env('SAML_DEFAULT_ROLE', 'editor'),
    'sync_roles_strict' => env('SAML_SYNC_STRICT', false),
    'update_user_attributes_on_login' => env('SAML_UPDATE_ON_LOGIN', true),
];
