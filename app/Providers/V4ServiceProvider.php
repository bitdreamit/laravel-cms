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
        // Route files are loaded by bootstrap/app.php
        // Scheduled commands are loaded by routes/console.php
        // Only register event listeners and Blade directives here.

        $this->registerListeners();
        $this->registerBladeDirectives();
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
                if (function_exists('tenant_has_feature') && tenant_has_feature('ai_rag')) {
                    \App\Domain\Rag\Jobs\IndexEntry::dispatch($event->entry->id);
                }
            }
        );
    }

    protected function registerBladeDirectives(): void
    {
        \Illuminate\Support\Facades\Blade::directive('personalizeBlock', function ($expression) {
            return "<?php if(!in_array({$expression}, session('personalization.hidden_blocks', []))): ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('endPersonalizeBlock', function () {
            return "<?php endif; ?>";
        });
    }
}
