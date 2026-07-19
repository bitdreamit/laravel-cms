<?php

use App\Http\Controllers\Admin\DomainController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\WorkflowController;
use App\Http\Controllers\Admin\ExperimentController;
use App\Http\Controllers\Admin\RagController;
use App\Http\Controllers\Admin\SegmentController;
use App\Http\Controllers\Admin\PersonalizationRuleController;
use App\Http\Controllers\Admin\SamlIdpController;
use App\Http\Controllers\Admin\ScimTokenController;
use App\Http\Controllers\Admin\AuditStreamController;
use App\Http\Controllers\Admin\ConnectorController;
use App\Http\Controllers\Admin\FeatureFlagController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {

    // Domains (V4)
    Route::resource('domains', DomainController::class);
    Route::post('/domains/{id}/verify-dns', [DomainController::class, 'verifyDns']);
    Route::post('/domains/{id}/request-ssl', [DomainController::class, 'requestSsl']);
    Route::post('/domains/{id}/renew-ssl', [DomainController::class, 'renewSsl']);
    Route::post('/domains/{id}/activate-theme', [DomainController::class, 'activateTheme']);
    Route::post('/domains/{id}/activate-site', [DomainController::class, 'activateSite']);

    // Themes (V3 retained, V4 enhancements)
    Route::resource('themes', ThemeController::class);

    // Workflows (V4)
    Route::resource('workflows', WorkflowController::class);
    Route::post('/workflows/{id}/start', [WorkflowController::class, 'start']);
    Route::post('/workflow-instances/{id}/advance', [WorkflowController::class, 'advance']);
    Route::post('/workflow-instances/{id}/cancel', [WorkflowController::class, 'cancel']);

    // Experiments (V4)
    Route::resource('experiments', ExperimentController::class);
    Route::post('/experiments/{id}/promote-winner', [ExperimentController::class, 'promoteWinner']);

    // RAG (V4)
    Route::get('/rag/playground', [RagController::class, 'playground']);
    Route::post('/rag/ask', [RagController::class, 'ask']);
    Route::get('/rag/index-status', [RagController::class, 'indexStatus']);
    Route::post('/rag/reindex/{entryId}', [RagController::class, 'reindexEntry']);
    Route::get('/rag/queries-log', [RagController::class, 'queriesLog']);

    // Segments & Personalization (V4)
    Route::resource('segments', SegmentController::class);
    Route::resource('personalization-rules', PersonalizationRuleController::class);

    // SAML IdPs (V4)
    Route::resource('saml-idps', SamlIdpController::class);
    Route::post('/saml-idps/{id}/test-login', [SamlIdpController::class, 'testLogin']);

    // SCIM Tokens (V4)
    Route::resource('scim-tokens', ScimTokenController::class)->only(['index', 'store', 'destroy']);

    // Audit Streams (V4)
    Route::resource('audit-streams', AuditStreamController::class);
    Route::post('/audit-streams/{id}/test', [AuditStreamController::class, 'testConnection']);
    Route::post('/audit-streams/{id}/retry-failed', [AuditStreamController::class, 'retryFailed']);

    // Connectors (V4)
    Route::resource('connectors', ConnectorController::class);
    Route::post('/connectors/{id}/revoke', [ConnectorController::class, 'revoke']);

    // Feature Flags (V4)
    Route::get('/feature-flags', [FeatureFlagController::class, 'index']);
    Route::post('/feature-flags', [FeatureFlagController::class, 'update']);
});
