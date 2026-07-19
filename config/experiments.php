<?php

return [
    'enabled' => env('EXPERIMENTS_ENABLED', true),
    'default_traffic_allocation' => env('EXPERIMENTS_DEFAULT_TRAFFIC', 100),
    'default_confidence_threshold' => env('EXPERIMENTS_CONFIDENCE', 0.95),
    'default_min_sample_size' => env('EXPERIMENTS_MIN_SAMPLE', 100),
    'visitor_cookie' => [
        'name' => 'cms_visitor_id',
        'minutes' => 60 * 24 * 365, // 1 year
    ],
    'auto_promote' => env('EXPERIMENTS_AUTO_PROMOTE', false),
    'cleanup_archived_days' => env('EXPERIMENTS_CLEANUP_DAYS', 180),
];
