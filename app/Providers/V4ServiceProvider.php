<?php

namespace App\Providers;

use App\Domain\Audit\Services\AuditStreamManager;
use App\Domain\Audit\Services\ChainHasher;
use App\Domain\Connector\Services\AuthBridgeService;
use App\Domain\Connector\Services\ConnectorManager;
use App\Domain\Dns\Services\AcmeClient;
use App\Domain\Dns\Services\DnsVerificationService;
use App\Domain\Dns\Services\SslCertificateManager;
use App\Domain\Experiment\Services\ExperimentEngine;
use App\Domain\Personalization\Services\SegmentEvaluator;
use App\Domain\Rag\Services\Chunker;
use App\Domain\Rag\Services\CitationFormatter;
use App\Domain\Rag\Services\EmbeddingService;
use App\Domain\Rag\Services\RagService;
use App\Domain\Rag\Services\VectorSearch;
use App\Domain\Workflow\Services\ConditionEvaluator;
use App\Domain\Workflow\Services\WorkflowEngine;
use Illuminate\Support\ServiceProvider;
use Spatie\Dns\Dns;

class V4ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // DNS Domain
        $this->app->singleton(DnsVerificationService::class, function ($app) {
            return new DnsVerificationService(new Dns());
        });

        $this->app->singleton(AcmeClient::class, function ($app) {
            $provider = config('ssl.default_provider', 'letsencrypt');
            $url = config("ssl.providers.{$provider}.directory_url");
            $apiKey = config("ssl.providers.{$provider}.api_key");
            return new AcmeClient($url, $apiKey);
        });

        $this->app->singleton(SslCertificateManager::class);

        // Workflow Domain
        $this->app->singleton(WorkflowEngine::class);
        $this->app->singleton(ConditionEvaluator::class);

        // Experiment Domain
        $this->app->singleton(ExperimentEngine::class);

        // RAG Domain
        $this->app->singleton(Chunker::class);
        $this->app->singleton(EmbeddingService::class);
        $this->app->singleton(VectorSearch::class);
        $this->app->singleton(CitationFormatter::class);
        $this->app->singleton(RagService::class);

        // Personalization Domain
        $this->app->singleton(SegmentEvaluator::class);

        // Audit Domain
        $this->app->singleton(ChainHasher::class);
        $this->app->singleton(AuditStreamManager::class);

        // Connector Domain
        $this->app->singleton(ConnectorManager::class);
        $this->app->singleton(AuthBridgeService::class);
    }

    public function boot(): void
    {
        // Route files are loaded by bootstrap/app.php (withRouting then: closure).
        // Do NOT load routes here — they're already registered.

        // Register event listeners
        $this->registerListeners();

        // Register Blade directives
        $this->registerBladeDirectives();

        // Scheduled commands are registered in routes/console.php
        // Do NOT register them here — would cause duplicate schedule entries.
    }

    protected function registerRoutes(): void
    {
        \Illuminate\Support\Facades\Route::middleware(['web', 'saml'])
            ->group(base_path('routes/saml.php'));

        \Illuminate\Support\Facades\Route::middleware(['api', 'scim-auth'])
            ->prefix('scim/v2')
            ->group(base_path('routes/scim.php'));

        \Illuminate\Support\Facades\Route::middleware(['api'])
            ->prefix('api/v1/connector')
            ->group(base_path('routes/connector.php'));

        \Illuminate\Support\Facades\Route::middleware(['web'])
            ->group(base_path('routes/collab.php'));

        \Illuminate\Support\Facades\Route::middleware(['web'])
            ->group(base_path('routes/tenant-web.php'));

        \Illuminate\Support\Facades\Route::middleware(['web', 'auth'])
            ->prefix('admin')
            ->group(base_path('routes/tenant-admin.php'));

        \Illuminate\Support\Facades\Route::middleware(['api'])
            ->prefix('api/v1')
            ->group(base_path('routes/api.php'));
    }

    protected function registerListeners(): void
    {
        // Workflow events
        \Illuminate\Support\Facades\Event::listen(
            \App\Domain\Workflow\Events\ApprovalRequired::class,
            \App\Domain\Workflow\Listeners\NotifyApprovers::class,
        );

        // RAG indexing on entry publish
        \Illuminate\Support\Facades\Event::listen(
            \App\Domain\Content\Events\EntryPublished::class,
            function (\App\Domain\Content\Events\EntryPublished $event) {
                if (tenant_has_feature('ai_rag')) {
                    \App\Domain\Rag\Jobs\IndexEntry::dispatch($event->entry->id);
                }
            }
        );

        // Audit streaming
        \Illuminate\Support\Facades\Event::listen(
            'Spatie\Activitylog\Events\ActivityLogged',
            function ($event) {
                if (tenant_has_feature('audit_streaming')) {
                    app(AuditStreamManager::class)->onActivityLogged($event->activity);
                }
            }
        );
    }

    protected function registerBladeDirectives(): void
    {
        \Illuminate\Support\Facades\Blade::directive('theme', function ($expression) {
            return "<?php echo app('current.theme')?->settings[{$expression}] ?? ''; ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('personalizeBlock', function ($expression) {
            return "<?php if(!in_array({$expression}, session('personalization.hidden_blocks', []))): ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('endPersonalizeBlock', function () {
            return "<?php endif; ?>";
        });
    }

    protected function registerScheduledCommands(): void
    {
        if (! $this->app->runningInConsole()) return;

        \Illuminate\Support\Facades\Schedule::command('ssl:renew')->dailyAt('02:00');
        \Illuminate\Support\Facades\Schedule::command('dns:retry-failed')->hourly();
        \Illuminate\Support\Facades\Schedule::command('audit:verify-chain')->weekly();
        \Illuminate\Support\Facades\Schedule::command('workflow:check-sla-breaches')->dailyAt('08:00');
        \Illuminate\Support\Facades\Schedule::command('experiments:check-winners')->hourly();
        \Illuminate\Support\Facades\Schedule::command('rag:reindex-stale')->dailyAt('03:00');
        \Illuminate\Support\Facades\Schedule::command('collab:cleanup-stale-sessions')->everyFifteenMinutes();
        \Illuminate\Support\Facades\Schedule::command('audit:retry-failed-deliveries')->everyFiveMinutes();
    }
}
