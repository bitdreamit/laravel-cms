<?php

return [
    'default_disk' => env('MEDIA_DISK', 'public'),
    'default_container' => 'main',

    'disks' => [
        'local' => ['driver' => 'local', 'root' => storage_path('app/public')],
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('MEDIA_S3_BUCKET'),
        ],
    ],

    'image_manipulation' => [
        'enabled' => env('MEDIA_IMAGE_MANIPULATION', true),
        'cache_path' => storage_path('app/public/img-cache'),
        'presets' => [
            'thumbnail' => ['w' => 150, 'h' => 150, 'fit' => 'crop'],
            'medium' => ['w' => 600, 'h' => null, 'fit' => 'max'],
            'large' => ['w' => 1200, 'h' => null, 'fit' => 'max'],
            'social' => ['w' => 1200, 'h' => 630, 'fit' => 'crop'],
        ],
    ],

    'allowed_mime_types' => [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'application/pdf', 'application/zip',
        'text/plain', 'text/csv',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],

    'max_file_size' => env('MEDIA_MAX_FILE_SIZE', 52428800), // 50MB

    'focal_point' => [
        'enabled' => true,
    ],
];
