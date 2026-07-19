<?php

return [
    'default_gateway' => env('BILLING_DEFAULT_GATEWAY', 'stripe'),

    'gateways' => [
        'stripe' => [
            'secret' => env('STRIPE_SECRET'),
            'public' => env('STRIPE_PUBLIC'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
        'sslcommerz' => [
            'store_id' => env('SSLCOMMERZ_STORE_ID'),
            'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
            'sandbox' => env('SSLCOMMERZ_SANDBOX', true),
        ],
        'bkash' => [
            'app_id' => env('BKASH_APP_ID'),
            'app_secret' => env('BKASH_APP_SECRET'),
            'username' => env('BKASH_USERNAME'),
            'password' => env('BKASH_PASSWORD'),
            'sandbox' => env('BKASH_SANDBOX', true),
        ],
    ],

    'currency' => [
        'default' => env('BILLING_DEFAULT_CURRENCY', 'USD'),
        'supported' => ['USD', 'EUR', 'GBP', 'BDT'],
    ],

    'invoices' => [
        'prefix' => 'INV',
        'due_days' => 30,
        'grace_period_days' => 7,
        'auto_suspend_overdue' => env('BILLING_AUTO_SUSPEND', true),
    ],

    'recurring' => [
        'enabled' => env('BILLING_RECURRING', true),
        'reminder_days_before' => [7, 1],
        'reminder_days_after' => [1, 7, 14],
    ],

    'tax' => [
        'default_rate' => 0,
        'inclusive' => false,
    ],
];
