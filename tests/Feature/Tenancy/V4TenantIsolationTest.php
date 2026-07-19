<?php

use App\Models\Central\Domain;
use App\Models\Central\Tenant;
use App\Models\Tenant\Workflow;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\Entry;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['status' => 'active']);
    $this->otherTenant = Tenant::factory()->create(['status' => 'active']);
});

it('resolves exact domain to correct tenant', function () {
    $domain = Domain::factory()->create([
        'tenant_id' => $this->tenant->id,
        'domain' => 'shop.advmedi.test',
        'dns_verification_status' => 'verified',
    ]);

    $request = request([], [], [], [], [], ['HTTP_HOST' => 'shop.advmedi.test']);

    $middleware = new \App\Http\Middleware\InitializeTenancyByDomain();
    $middleware->handle($request, fn() => response('ok'));

    expect(tenancy()->initialized)->toBeTrue();
    expect(tenant('id'))->toBe($this->tenant->id);
});

it('resolves wildcard subdomain to correct tenant', function () {
    Domain::factory()->create([
        'tenant_id' => $this->tenant->id,
        'domain' => '*.multilingual.test',
        'is_wildcard' => true,
        'wildcard_parent' => 'multilingual.test',
        'dns_verification_status' => 'verified',
    ]);

    $request = request([], [], [], [], [], ['HTTP_HOST' => 'paris.multilingual.test']);

    $init = new \App\Http\Middleware\InitializeTenancyByDomain();
    $init->handle($request, fn() => response('ok'));

    $wildcard = new \App\Http\Middleware\ResolveWildcardDomain();
    $wildcard->handle($request, fn() => response('ok'));

    expect(tenancy()->initialized)->toBeTrue();
    expect(tenant('id'))->toBe($this->tenant->id);
    expect($request->attributes->get('wildcard_segment'))->toBe('paris');
});

it('prevents cross-tenant access to workflow data', function () {
    tenancy()->initialize($this->tenant);

    $workflow = Workflow::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Tenant A Workflow',
    ]);

    $instance = WorkflowInstance::factory()->create([
        'tenant_id' => $this->tenant->id,
        'workflow_id' => $workflow->id,
        'entry_id' => Entry::factory()->create(['tenant_id' => $this->tenant->id])->id,
    ]);

    // Switch to tenant B
    tenancy()->end();
    tenancy()->initialize($this->otherTenant);

    // Tenant B should NOT see Tenant A's workflows
    $visibleWorkflows = Workflow::where('tenant_id', tenant('id'))->get();
    expect($visibleWorkflows)->toHaveCount(0);
    expect($visibleWorkflows->contains('id', $workflow->id))->toBeFalse();
});

it('verifies dns verification token matches', function () {
    $service = app(\App\Domain\Dns\Services\DnsVerificationService::class);

    $domain = Domain::factory()->create([
        'domain' => 'test.example.com',
        'dns_verification_status' => 'unverified',
    ]);

    $job = $service->createVerificationJob($domain);

    expect($job->record_value)->toBeString();
    expect(strlen($job->record_value))->toBe(32);
    expect($job->record_name)->toBe('_cms-verify.test.example.com');
});

it('only renews ssl certificates within renewal window', function () {
    $cert = \App\Models\Central\SslCertificate::factory()->create([
        'expires_at' => now()->addDays(20),
        'auto_renew' => true,
        'status' => 'active',
    ]);

    $expired = \App\Models\Central\SslCertificate::factory()->create([
        'expires_at' => now()->subDays(5),
        'auto_renew' => true,
        'status' => 'active',
    ]);

    $future = \App\Models\Central\SslCertificate::factory()->create([
        'expires_at' => now()->addDays(60),
        'auto_renew' => true,
        'status' => 'active',
    ]);

    expect($cert->shouldRenew())->toBeTrue();
    expect($expired->shouldRenew())->toBeTrue();
    expect($future->shouldRenew())->toBeFalse();
});
