<?php

return [
    'guard' => 'web',
    'middleware' => ['web'],
    'prefix' => env('FORTIFY_PREFIX', 'admin'),
    'views' => true,

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
    ],

    'features' => [
        Laravel\Fortify\Features::Registration(),
        Laravel\Fortify\Features::ResetPasswords(),
        Laravel\Fortify\Features::EmailVerification(),
        Laravel\Fortify\Features::UpdateProfileInformation(),
        Laravel\Fortify\Features::UpdatePasswords(),
        Laravel\Fortify\Features::TwoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]),
    ],

    'two_factor' => [
        'secret_length' => 32,
        'backup_codes_count' => 8,
    ],

    'passwords' => 'users',
    'password_rules' => [
        'min' => 8,
        'mixed_case' => true,
        'numbers' => true,
        'symbols' => false,
    ],
];
