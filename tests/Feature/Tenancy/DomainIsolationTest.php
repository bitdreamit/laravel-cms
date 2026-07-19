<?php

use App\Models\Central\Tenant;
use App\Models\Central\Domain;
use Stancl\Tenancy\Tenancy;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['status' => 'active']);
});

it('resolves exact domain to correct tenant', function () {
    $domain = Domain::factory()->create(['tenant_id' => $this->tenant->id, 'domain' => 'test.example.com']);

    $request = request([], [], [], [], [], ['HTTP_HOST' => 'test.example.com']);
    $middleware = new \App\Http\Middleware\InitializeTenancyByDomain();
    $middleware->handle($request, fn() => response('ok'));

    expect(tenancy()->initialized)->toBeTrue();
    expect(tenant('id'))->toBe($this->tenant->id);
});

it('resolves wildcard subdomain to correct tenant', function () {
    Domain::factory()->create(['tenant_id' => $this->tenant->id, 'domain' => '*.example.com', 'is_wildcard' => true, 'wildcard_parent' => 'example.com']);

    $request = request([], [], [], [], [], ['HTTP_HOST' => 'shop.example.com']);
    $init = new \App\Http\Middleware\InitializeTenancyByDomain();
    $init->handle($request, fn() => response('ok'));

    $wildcard = new \App\Http\Middleware\ResolveWildcardDomain();
    $wildcard->handle($request, fn() => response('ok'));

    expect(tenancy()->initialized)->toBeTrue();
    expect(tenant('id'))->toBe($this->tenant->id);
    expect($request->attributes->get('wildcard_segment'))->toBe('shop');
});

it('extracts wildcard segment correctly', function () {
    $domain = Domain::factory()->create(['domain' => '*.example.com', 'is_wildcard' => true, 'wildcard_parent' => 'example.com']);
    expect($domain->extractWildcardSegment('shop.example.com'))->toBe('shop');
    expect($domain->extractWildcardSegment('blog.example.com'))->toBe('blog');
    expect($domain->extractWildcardSegment('example.com'))->toBeNull();
});

it('matches wildcard domain correctly', function () {
    $domain = Domain::factory()->create(['domain' => '*.example.com', 'is_wildcard' => true]);
    expect($domain->matchesHost('shop.example.com'))->toBeTrue();
    expect($domain->matchesHost('blog.example.com'))->toBeTrue();
    expect($domain->matchesHost('example.com'))->toBeFalse();
    expect($domain->matchesHost('other.com'))->toBeFalse();
});

it('returns 503 for parked domains', function () {
    $domain = Domain::factory()->create(['tenant_id' => $this->tenant->id, 'domain' => 'parked.test', 'status' => 'parked']);
    tenancy()->initialize($this->tenant);
    app()->instance('current.domain', $domain);

    $request = request([], [], [], [], [], ['HTTP_HOST' => 'parked.test']);
    $middleware = new \App\Http\Middleware\VerifyDomainActive();
    $response = $middleware->handle($request, fn() => response('ok'));

    expect($response->getStatusCode())->toBe(503);
});

it('redirects for redirect_only domains', function () {
    $domain = Domain::factory()->create(['tenant_id' => $this->tenant->id, 'domain' => 'redirect.test', 'status' => 'redirect_only', 'redirect_target' => 'https://target.test']);
    tenancy()->initialize($this->tenant);
    app()->instance('current.domain', $domain);

    $request = request([], [], [], [], [], ['HTTP_HOST' => 'redirect.test']);
    $middleware = new \App\Http\Middleware\VerifyDomainActive();
    $response = $middleware->handle($request, fn() => response('ok'));

    expect($response->getStatusCode())->toBe(301);
    expect($response->headers->get('Location'))->toBe('https://target.test');
});
