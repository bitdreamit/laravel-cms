<?php

use App\Models\Central\Tenant;
use App\Models\Central\Domain;
use App\Models\Central\User;
use App\Models\Tenant\Entry;
use App\Models\Tenant\Collection;
use App\Models\Tenant\Blueprint;
use App\Models\Tenant\Taxonomy;
use App\Models\Tenant\Term;
use App\Models\Tenant\Form;
use App\Models\Tenant\FormSubmission;
use App\Models\Tenant\GlobalVariable;
use App\Models\Tenant\Navigation;
use App\Models\Tenant\Asset;
use App\Models\Tenant\AssetContainer;
use App\Models\Tenant\Redirect;
use App\Models\Tenant\Webhook;
use App\Models\Tenant\Workflow;
use App\Models\Tenant\Experiment;
use App\Models\Tenant\Segment;
use App\Models\Tenant\PersonalizationRule;
use App\Models\Tenant\SamlIdentityProvider;
use App\Models\Tenant\AuditStream;
use Stancl\Tenancy\Tenancy;

beforeEach(function () {
    $this->tenantA = Tenant::factory()->create(['status' => 'active']);
    $this->tenantB = Tenant::factory()->create(['status' => 'active']);
    $this->userA = User::factory()->create();
    $this->userB = User::factory()->create();
});

it('prevents cross-tenant access to entries', function () {
    tenancy()->initialize($this->tenantA);
    $entryA = Entry::factory()->create(['tenant_id' => $this->tenantA->id, 'title' => 'Tenant A Entry']);

    tenancy()->end();
    tenancy()->initialize($this->tenantB);
    $entriesB = Entry::where('tenant_id', $this->tenantB->id)->get();
    expect($entriesB)->toHaveCount(0);
    expect(Entry::where('id', $entryA->id)->exists())->toBeFalse();
});

it('prevents cross-tenant access to collections', function () {
    tenancy()->initialize($this->tenantA);
    $colA = Collection::factory()->create(['tenant_id' => $this->tenantA->id, 'handle' => 'tenant-a-collection']);

    tenancy()->end();
    tenancy()->initialize($this->tenantB);
    expect(Collection::where('handle', 'tenant-a-collection')->exists())->toBeFalse();
});

it('prevents cross-tenant access to blueprints', function () {
    tenancy()->initialize($this->tenantA);
    $bpA = Blueprint::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'handle' => 'bp-a', 'title' => 'BP A', 'type' => 'collection', 'tabs' => []]);

    tenancy()->end();
    tenancy()->initialize($this->tenantB);
    expect(Blueprint::where('handle', 'bp-a')->exists())->toBeFalse();
});

it('prevents cross-tenant access to taxonomies', function () {
    tenancy()->initialize($this->tenantA);
    $taxA = Taxonomy::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'name' => 'Categories A', 'handle' => 'cat-a']);

    tenancy()->end();
    tenancy()->initialize($this->tenantB);
    expect(Taxonomy::where('handle', 'cat-a')->exists())->toBeFalse();
});

it('prevents cross-tenant access to forms', function () {
    tenancy()->initialize($this->tenantA);
    $formA = Form::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'name' => 'Form A', 'handle' => 'form-a', 'fields' => []]);

    tenancy()->end();
    tenancy()->initialize($this->tenantB);
    expect(Form::where('handle', 'form-a')->exists())->toBeFalse();
});

it('prevents cross-tenant access to globals', function () {
    tenancy()->initialize($this->tenantA);
    $globalA = GlobalVariable::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'name' => 'Settings A', 'handle' => 'settings-a', 'data' => []]);

    tenancy()->end();
    tenancy()->initialize($this->tenantB);
    expect(GlobalVariable::where('handle', 'settings-a')->exists())->toBeFalse();
});

it('prevents cross-tenant access to navigations', function () {
    tenancy()->initialize($this->tenantA);
    $navA = Navigation::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'name' => 'Nav A', 'handle' => 'nav-a']);

    tenancy()->end();
    tenancy()->initialize($this->tenantB);
    expect(Navigation::where('handle', 'nav-a')->exists())->toBeFalse();
});

it('prevents cross-tenant access to assets', function () {
    tenancy()->initialize($this->tenantA);
    $containerA = AssetContainer::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'name' => 'Main A', 'handle' => 'main-a', 'disk' => 'public']);
    $assetA = Asset::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'container_id' => $containerA->id, 'filename' => 'test.jpg', 'path' => 'test.jpg', 'mime_type' => 'image/jpeg', 'size' => 100]);

    tenancy()->end();
    tenancy()->initialize($this->tenantB);
    expect(Asset::where('id', $assetA->id)->exists())->toBeFalse();
});

it('prevents cross-tenant access to redirects', function () {
    tenancy()->initialize($this->tenantA);
    $redirectA = Redirect::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'source_url' => '/old-a', 'destination_url' => '/new-a']);

    tenancy()->end();
    tenancy()->initialize($this->tenantB);
    expect(Redirect::where('source_url', '/old-a')->exists())->toBeFalse();
});

it('prevents cross-tenant access to webhooks', function () {
    tenancy()->initialize($this->tenantA);
    $webhookA = Webhook::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'name' => 'Webhook A', 'url' => 'https://a.test/hook', 'secret' => 'secret', 'subscribed_events' => ['entry.published']]);

    tenancy()->end();
    tenancy()->initialize($this->tenantB);
    expect(Webhook::where('url', 'https://a.test/hook')->exists())->toBeFalse();
});

it('prevents cross-tenant access to workflows', function () {
    tenancy()->initialize($this->tenantA);
    $wfA = Workflow::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'name' => 'WF A', 'handle' => 'wf-a', 'trigger_event' => 'entry.created', 'trigger_collections' => [], 'definition' => ['nodes' => []]]);

    tenancy()->end();
    tenancy()->initialize($this->tenantB);
    expect(Workflow::where('handle', 'wf-a')->exists())->toBeFalse();
});

it('prevents cross-tenant access to experiments', function () {
    tenancy()->initialize($this->tenantA);
    $expA = Experiment::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'name' => 'Exp A', 'handle' => 'exp-a', 'experiment_type' => 'entry_variant', 'goal_type' => 'conversion']);

    tenancy()->end();
    tenancy()->initialize($this->tenantB);
    expect(Experiment::where('handle', 'exp-a')->exists())->toBeFalse();
});

it('prevents cross-tenant access to segments', function () {
    tenancy()->initialize($this->tenantA);
    $segA = Segment::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'name' => 'Seg A', 'handle' => 'seg-a', 'rules' => []]);

    tenancy()->end();
    tenancy()->initialize($this->tenantB);
    expect(Segment::where('handle', 'seg-a')->exists())->toBeFalse();
});

it('prevents cross-tenant access to personalization rules', function () {
    tenancy()->initialize($this->tenantA);
    $segA = Segment::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'name' => 'Seg A', 'handle' => 'seg-a', 'rules' => []]);
    $ruleA = PersonalizationRule::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'name' => 'Rule A', 'handle' => 'rule-a', 'segment_id' => $segA->id, 'target_type' => 'redirect', 'target_config' => []]);

    tenancy()->end();
    tenancy()->initialize($this->tenantB);
    expect(PersonalizationRule::where('handle', 'rule-a')->exists())->toBeFalse();
});

it('prevents cross-tenant access to SAML IdPs', function () {
    tenancy()->initialize($this->tenantA);
    $idpA = SamlIdentityProvider::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'name' => 'IdP A', 'entity_id' => 'a', 'metadata_xml' => '', 'sso_url' => 'https://a.test/sso', 'x509_certificate' => 'cert']);

    tenancy()->end();
    tenancy()->initialize($this->tenantB);
    expect(SamlIdentityProvider::where('entity_id', 'a')->exists())->toBeFalse();
});

it('prevents cross-tenant access to audit streams', function () {
    tenancy()->initialize($this->tenantA);
    $streamA = AuditStream::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'name' => 'Stream A', 'destination_type' => 'http_webhook', 'destination_config' => ['url' => 'https://a.test/log']]);

    tenancy()->end();
    tenancy()->initialize($this->tenantB);
    expect(AuditStream::where('name', 'Stream A')->exists())->toBeFalse();
});

it('prevents cross-tenant access to form submissions', function () {
    tenancy()->initialize($this->tenantA);
    $formA = Form::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'name' => 'Form A', 'handle' => 'form-a', 'fields' => []]);
    $subA = FormSubmission::create(['id' => \Str::uuid(), 'tenant_id' => $this->tenantA->id, 'form_id' => $formA->id, 'data' => ['name' => 'Test A']]);

    tenancy()->end();
    tenancy()->initialize($this->tenantB);
    expect(FormSubmission::where('id', $subA->id)->exists())->toBeFalse();
});

it('prevents cross-tenant access to domains via central table', function () {
    $domainA = Domain::factory()->create(['tenant_id' => $this->tenantA->id, 'domain' => 'tenant-a.test']);
    $domainB = Domain::factory()->create(['tenant_id' => $this->tenantB->id, 'domain' => 'tenant-b.test']);

    expect(Domain::where('tenant_id', $this->tenantA->id)->pluck('domain')->toArray())->toContain('tenant-a.test');
    expect(Domain::where('tenant_id', $this->tenantA->id)->pluck('domain')->toArray())->not->toContain('tenant-b.test');
});
