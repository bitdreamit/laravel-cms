<?php

return [
    'enabled' => env('PERSONALIZATION_ENABLED', true),

    'visitor' => [
        'cookie_name' => 'cms_visitor_id',
        'cookie_minutes' => 60 * 24 * 365, // 1 year
    ],

    'session' => [
        'cache_ttl_minutes' => env('PERSONALIZATION_SESSION_TTL', 60),
        'tracking_enabled' => env('PERSONALIZATION_TRACKING', true),
    ],

    'conditions' => [
        'visit_count' => \App\Domain\Personalization\Conditions\VisitCountCondition::class,
        'first_visit_at' => \App\Domain\Personalization\Conditions\FirstVisitAtCondition::class,
        'last_visit_at' => \App\Domain\Personalization\Conditions\LastVisitAtCondition::class,
        'geo_country' => \App\Domain\Personalization\Conditions\GeoCountryCondition::class,
        'geo_region' => \App\Domain\Personalization\Conditions\GeoRegionCondition::class,
        'geo_city' => \App\Domain\Personalization\Conditions\GeoCityCondition::class,
        'device_type' => \App\Domain\Personalization\Conditions\DeviceTypeCondition::class,
        'browser' => \App\Domain\Personalization\Conditions\BrowserCondition::class,
        'referrer' => \App\Domain\Personalization\Conditions\ReferrerCondition::class,
        'landing_page' => \App\Domain\Personalization\Conditions\LandingPageCondition::class,
        'query_param' => \App\Domain\Personalization\Conditions\QueryParamCondition::class,
        'cookie' => \App\Domain\Personalization\Conditions\CookieCondition::class,
        'user_role' => \App\Domain\Personalization\Conditions\UserRoleCondition::class,
        'user_tag' => \App\Domain\Personalization\Conditions\UserTagCondition::class,
        'viewed_entry' => \App\Domain\Personalization\Conditions\ViewedEntryCondition::class,
        'submitted_form' => \App\Domain\Personalization\Conditions\SubmittedFormCondition::class,
        'time_of_day' => \App\Domain\Personalization\Conditions\TimeOfDayCondition::class,
        'day_of_week' => \App\Domain\Personalization\Conditions\DayOfWeekCondition::class,
        'experiment_variant' => \App\Domain\Personalization\Conditions\ExperimentVariantCondition::class,
    ],

    'geoip' => [
        'database_path' => env('GEOIP_DB_PATH', database_path('geoip/GeoLite2-City.mmdb')),
        'license_key' => env('MAXMIND_LICENSE_KEY'),
    ],

    'rules' => [
        'max_per_request' => env('PERSONALIZATION_MAX_RULES', 50),
        'default_priority' => 100,
    ],
];
