<?php

namespace App\Providers;

use App\Models\Central\Domain;
use App\Models\Central\Theme;
use App\Models\Tenant\Blueprint;
use App\Models\Tenant\Entry;
use App\Observers\BlueprintObserver;
use App\Observers\DomainObserver;
use App\Observers\EntryObserver;
use App\Observers\ThemeObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // V3 Content events
        \App\Domain\Content\Events\EntryCreated::class => [
            \App\Domain\Content\Listeners\InvalidateEntryCache::class,
        ],
        \App\Domain\Content\Events\EntryUpdated::class => [
            \App\Domain\Content\Listeners\InvalidateEntryCache::class,
        ],
        \App\Domain\Content\Events\EntryPublished::class => [
            \App\Domain\Content\Listeners\InvalidateEntryCache::class,
            \App\Domain\Content\Listeners\DispatchWebhooks::class,
        ],
        \App\Domain\Content\Events\EntryDeleted::class => [
            \App\Domain\Content\Listeners\InvalidateEntryCache::class,
        ],
        \App\Domain\Content\Events\FormSubmitted::class => [
            \App\Domain\Content\Listeners\DispatchWebhooks::class,
        ],

        // V3 Tenancy events
        \App\Domain\Tenancy\Events\TenantCreated::class => [],
        \App\Domain\Tenancy\Events\TenantSuspended::class => [],

        // V3 Billing events
        \App\Domain\Billing\Events\InvoicePaid::class => [
            \App\Domain\Billing\Listeners\SendInvoiceEmail::class,
        ],

        // V4 DNS events
        \App\Domain\Dns\Events\SslCertificateIssued::class => [],
        \App\Domain\Dns\Events\SslCertificateRenewed::class => [],
        \App\Domain\Dns\Events\SslCertificateFailed::class => [],

        // V4 Workflow events
        \App\Domain\Workflow\Events\WorkflowStarted::class => [],
        \App\Domain\Workflow\Events\WorkflowCompleted::class => [],
        \App\Domain\Workflow\Events\ApprovalRequired::class => [
            \App\Domain\Workflow\Listeners\NotifyApprovers::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        // Register observers
        Entry::observe(EntryObserver::class);
        Blueprint::observe(BlueprintObserver::class);
        Theme::observe(ThemeObserver::class);
        Domain::observe(DomainObserver::class);
    }
}
