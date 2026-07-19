<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('handle');
            $table->string('locale', 10)->default('en');
            $table->boolean('is_default')->default(false);
            $table->string('url')->nullable();
            $table->json('attributes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'handle']);
        });

        Schema::create('blueprints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('handle');
            $table->string('title');
            $table->string('type')->default('collection');
            $table->string('icon')->nullable();
            $table->json('tabs')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'handle']);
        });

        Schema::create('blueprint_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('blueprint_id');
            $table->uuid('tenant_id');
            $table->string('handle');
            $table->string('display_label');
            $table->text('instructions')->nullable();
            $table->string('fieldtype');
            $table->json('config')->nullable();
            $table->string('validation_rules')->nullable();
            $table->boolean('is_localizable')->default(false);
            $table->boolean('is_listable')->default(true);
            $table->boolean('is_sortable')->default(false);
            $table->json('conditional_logic')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('blueprint_id')->references('id')->on('blueprints')->cascadeOnDelete();
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('handle');
            $table->text('description')->nullable();
            $table->string('route_pattern')->nullable();
            $table->string('template')->nullable();
            $table->string('structure_mode')->default('flat');
            $table->integer('max_depth')->default(1);
            $table->string('default_status')->default('draft');
            $table->json('seo_settings')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_searchable')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'handle']);
        });

        Schema::create('collection_blueprints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('collection_id');
            $table->uuid('blueprint_id');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('collection_id')->references('id')->on('collections')->cascadeOnDelete();
            $table->foreign('blueprint_id')->references('id')->on('blueprints')->cascadeOnDelete();
        });

        Schema::create('entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('collection_id');
            $table->uuid('blueprint_id')->nullable();
            $table->uuid('site_id')->nullable();
            $table->string('title');
            $table->string('slug');
            $table->string('status')->default('draft');
            $table->json('data')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->uuid('author_id')->nullable();
            $table->string('template')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('collection_id')->references('id')->on('collections')->cascadeOnDelete();
            $table->foreign('site_id')->references('id')->on('sites')->nullOnDelete();
            $table->foreign('parent_id')->references('id')->on('entries')->nullOnDelete();
            $table->unique(['tenant_id', 'collection_id', 'slug']);
        });

        Schema::create('entry_revisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('entry_id');
            $table->integer('revision_number');
            $table->json('data');
            $table->uuid('user_id')->nullable();
            $table->string('action')->nullable();
            $table->string('summary')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('entry_id')->references('id')->on('entries')->cascadeOnDelete();
        });

        Schema::create('taxonomies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('handle');
            $table->text('description')->nullable();
            $table->boolean('is_hierarchical')->default(false);
            $table->integer('max_levels')->default(1);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'handle']);
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('taxonomy_id');
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('data')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('taxonomy_id')->references('id')->on('taxonomies')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('terms')->nullOnDelete();
            $table->unique(['tenant_id', 'taxonomy_id', 'slug']);
        });

        Schema::create('entry_terms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('entry_id');
            $table->uuid('term_id');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('entry_id')->references('id')->on('entries')->cascadeOnDelete();
            $table->foreign('term_id')->references('id')->on('terms')->cascadeOnDelete();
            $table->unique(['entry_id', 'term_id']);
        });

        Schema::create('globals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('handle');
            $table->json('data')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'handle']);
        });

        Schema::create('navigations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('handle');
            $table->integer('max_depth')->default(2);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'handle']);
        });

        Schema::create('navigation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('navigation_id');
            $table->uuid('parent_id')->nullable();
            $table->string('title');
            $table->string('url')->nullable();
            $table->uuid('entry_id')->nullable();
            $table->string('target')->default('_self');
            $table->integer('sort_order')->default(0);
            $table->json('data')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('navigation_id')->references('id')->on('navigations')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('navigation_items')->nullOnDelete();
        });

        Schema::create('forms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('handle');
            $table->text('description')->nullable();
            $table->json('fields')->nullable();
            $table->json('email_recipients')->nullable();
            $table->string('honeypot_field')->nullable();
            $table->text('success_message')->nullable();
            $table->text('error_message')->nullable();
            $table->string('redirect_url')->nullable();
            $table->boolean('store_submissions')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'handle']);
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('form_id');
            $table->string('visitor_id', 36)->nullable();
            $table->uuid('user_id')->nullable();
            $table->json('data');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->string('subject_type')->nullable();
            $table->uuid('subject_id')->nullable();
            $table->string('causer_type')->nullable();
            $table->uuid('causer_id')->nullable();
            $table->json('properties')->nullable();
            $table->string('previous_hash', 64)->nullable();
            $table->string('current_hash', 64)->nullable();
            $table->string('severity', 20)->default('info');
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('forms');
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('navigations');
        Schema::dropIfExists('globals');
        Schema::dropIfExists('entry_terms');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('taxonomies');
        Schema::dropIfExists('entry_revisions');
        Schema::dropIfExists('entries');
        Schema::dropIfExists('collection_blueprints');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('blueprint_fields');
        Schema::dropIfExists('blueprints');
        Schema::dropIfExists('sites');
    }
};
