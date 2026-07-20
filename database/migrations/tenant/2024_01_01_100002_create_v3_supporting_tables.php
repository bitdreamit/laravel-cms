<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_containers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('handle');
            $table->string('disk')->default('public');
            $table->string('title')->nullable();
            $table->integer('max_files')->default(0);
            $table->json('allowed_file_types')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'handle']);
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('container_id');
            $table->string('folder', 50)->default('/');
            $table->string('filename');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('title')->nullable();
            $table->json('focal_point')->nullable();
            $table->json('meta_data')->nullable();
            $table->uuid('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('container_id')->references('id')->on('asset_containers')->cascadeOnDelete();
            // Use 2-column index instead of 3-column to avoid MySQL 1000-byte index limit
            $table->index(['tenant_id', 'container_id']);
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('source_url');
            $table->string('destination_url');
            $table->integer('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'source_url']);
        });

        Schema::create('webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('url');
            $table->string('secret');
            $table->json('subscribed_events');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('saved_filters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('user_id');
            $table->string('name');
            $table->string('collection_handle');
            $table->json('filters');
            $table->boolean('is_shared')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'collection_handle']);
        });

        Schema::create('user_column_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('user_id');
            $table->string('collection_handle');
            $table->json('columns');
            $table->json('sort_order')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'collection_handle']);
        });

        Schema::create('user_nav_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('user_id');
            $table->json('nav_items');
            $table->json('pinned_items')->nullable();
            $table->json('hidden_items')->nullable();
            $table->timestamps();

            $table->unique(['user_id']);
        });

        Schema::create('import_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('source_type');
            $table->string('source_file')->nullable();
            $table->string('collection_handle');
            $table->string('status')->default('pending');
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('processed_items')->default(0);
            $table->unsignedInteger('failed_items')->default(0);
            $table->json('error_log')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->uuid('user_id')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // Spatie permission tables (team-scoped to tenant_id)
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 125);
            $table->string('guard_name', 25)->default('web');
            $table->uuid('team_id')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->unique(['name', 'guard_name', 'team_id']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 125);
            $table->string('guard_name', 25)->default('web');
            $table->uuid('team_id')->nullable();
            $table->timestamps();

            $table->unique(['name', 'guard_name', 'team_id']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->uuid('permission_id');
            $table->string('model_type', 125);
            $table->uuid('model_id');
            $table->uuid('team_id')->nullable();

            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
            $table->primary(['permission_id', 'model_id', 'model_type']);
            $table->index(['model_id', 'model_type']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->uuid('role_id');
            $table->string('model_type', 125);
            $table->uuid('model_id');
            $table->uuid('team_id')->nullable();

            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->primary(['role_id', 'model_id', 'model_type']);
            $table->index(['model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->uuid('permission_id');
            $table->uuid('role_id');

            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        // NOTE: jobs, failed_jobs, cache, and cache_locks tables are NOT created
        // per-tenant. They are central tables created in the central migration
        // (2024_01_01_000007_create_cache_table.php) because this project uses
        // QUEUE_CONNECTION=redis and CACHE_STORE=redis — the database queue/cache
        // driver is not used. Creating them per-tenant would be dead schema.
        //
        // If you switch to QUEUE_CONNECTION=database or CACHE_STORE=database,
        // these tables already exist in the central migration.

        // Visitor sessions (V4 personalization tracking)
        Schema::create('visitor_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('visitor_id', 36);
            $table->uuid('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('landing_page')->nullable();
            $table->string('referrer')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->timestamp('last_active_at')->useCurrent();
            $table->timestamps();

            $table->index(['tenant_id', 'visitor_id']);
        });

        Schema::create('visitor_session_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('visitor_session_id');
            $table->uuid('entry_id')->nullable();
            $table->string('url');
            $table->string('title')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->timestamp('viewed_at')->useCurrent();
            $table->timestamps();

            $table->index(['tenant_id', 'visitor_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_session_views');
        Schema::dropIfExists('visitor_sessions');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('import_jobs');
        Schema::dropIfExists('user_nav_preferences');
        Schema::dropIfExists('user_column_preferences');
        Schema::dropIfExists('saved_filters');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_containers');
    }
};
