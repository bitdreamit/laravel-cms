<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/
uses(\Tests\TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/
expect()->extend('toBeOneOf', function (array $expected) {
    return in_array($this->value, $expected);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/
function createTenantWithFeature(string $feature): \App\Models\Central\Tenant
{
    $tenant = \App\Models\Central\Tenant::factory()->create(['status' => 'active']);
    $data = $tenant->data ?? [];
    $data['features'] = [$feature];
    $tenant->data = $data;
    $tenant->save();

    return $tenant;
}
