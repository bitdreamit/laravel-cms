<?php

use App\Console\Commands\CheckWorkflowSla;
use App\Console\Commands\CleanupStaleCollabSessions;
use App\Console\Commands\RetryFailedAuditDeliveries;
use App\Console\Commands\RetryFailedDns;
use App\Console\Commands\ReindexRag;
use App\Console\Commands\RenewSslCertificates;
use App\Console\Commands\ScheduledMake;
use App\Console\Commands\VerifyAuditChain;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/**
 * CMS V6 — Scheduled Commands
 *
 * This file registers all recurring scheduled commands.
 * Production cron entry: * * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1
 */

// ============================================================
// V3 — Content
// ============================================================

// Publish scheduled entries (every minute — anything scheduled in the past gets published)
Schedule::command(ScheduledMake::class)
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// ============================================================
// V3 — Billing (revenue-critical — runs daily)
// ============================================================

// Generate recurring invoices for new billing periods
Schedule::command('billing:generate-invoices')
    ->dailyAt('00:00')
    ->withoutOverlapping();

// Send payment reminder emails (T-7 and T-1 from due date)
Schedule::command('billing:send-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping();

// Auto-suspend overdue tenants (configurable grace period per plan)
Schedule::command('billing:suspend-overdue')
    ->dailyAt('09:00')
    ->withoutOverlapping();

// Reactivate tenants whose overdue invoices got paid
Schedule::command('billing:reactivate-paid')
    ->hourly()
    ->withoutOverlapping();

// ============================================================
// V4 — SSL Automation & DNS
// ============================================================

Schedule::command(RenewSslCertificates::class)
    ->dailyAt('02:00')
    ->withoutOverlapping();

Schedule::command(RetryFailedDns::class)
    ->hourly()
    ->withoutOverlapping();

// ============================================================
// V4 — Audit Streaming
// ============================================================

Schedule::command(RetryFailedAuditDeliveries::class)
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command(VerifyAuditChain::class)
    ->weekly()
    ->sundays()
    ->at('03:00')
    ->withoutOverlapping();

// ============================================================
// V4 — Workflow Engine
// ============================================================

Schedule::command(CheckWorkflowSla::class)
    ->dailyAt('08:00')
    ->withoutOverlapping();

// ============================================================
// V4 — A/B Experiments
// ============================================================

// Check for experiments that reached significance and can be auto-promoted
Schedule::command('experiments:check-winners')
    ->hourly()
    ->withoutOverlapping();

// ============================================================
// V4 — AI RAG
// ============================================================

Schedule::command(ReindexRag::class)
    ->dailyAt('03:00')
    ->withoutOverlapping();

// ============================================================
// V4 — Collab Editing
// ============================================================

Schedule::command(CleanupStaleCollabSessions::class)
    ->everyFifteenMinutes()
    ->withoutOverlapping();

// ============================================================
// V6 — Backups
// ============================================================

// Daily full platform backup (kept 30 days)
Schedule::command('cms:backup --disk=local')
    ->dailyAt('04:00')
    ->withoutOverlapping();

// Prune old backups weekly
Schedule::command('cms:backup --prune=30')
    ->weekly()
    ->sundays()
    ->at('05:00')
    ->withoutOverlapping();

// ============================================================
// V6 — Health Checks & Monitoring
// ============================================================

// Clear stale cache locks every hour
Schedule::command('cache:prune-stale-tags')
    ->hourly();
