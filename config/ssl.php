<?php

return [
    'default_provider' => env('SSL_PROVIDER', 'letsencrypt'),
    'providers' => [
        'letsencrypt' => [
            'directory_url' => env('SSL_LE_DIRECTORY', 'https://acme-v02.api.letsencrypt.org/directory'),
            'environment' => env('SSL_ENV', 'production'),
        ],
        'zerossl' => [
            'directory_url' => 'https://acme.zerossl.com/v2/DV90',
            'api_key' => env('ZEROSSL_API_KEY'),
        ],
        'staging' => [
            'directory_url' => 'https://acme-staging-v02.api.letsencrypt.org/directory',
            'environment' => 'staging',
        ],
    ],
    'dns_providers' => [
        'cloudflare' => ['api_token' => env('CLOUDFLARE_API_TOKEN')],
        'route53' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ],
        'digitalocean' => ['token' => env('DIGITALOCEAN_API_TOKEN')],
    ],
    'webserver_reload_cmd' => env('SSL_RELOAD_CMD', 'sudo systemctl reload nginx'),
    'renewal_window_days' => env('SSL_RENEWAL_WINDOW', 30),
    'max_renewal_failures' => env('SSL_MAX_FAILURES', 5),
    'dns_verification' => [
        'record_prefix' => '_cms-verify',
        'max_attempts' => env('DNS_VERIFY_MAX_ATTEMPTS', 50),
        'attempt_interval_seconds' => env('DNS_VERIFY_INTERVAL', 300),
    ],
    'acme_challenge' => [
        'http' => ['well_known_path' => public_path('.well-known/acme-challenge')],
        'dns' => ['record_prefix' => '_acme-challenge'],
    ],
];
