<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_analytics_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('form_id');
            $table->string('visitor_id', 36);
            $table->string('event_type');
            $table->string('field_handle')->nullable();
            $table->json('event_data')->nullable();
            $table->string('page_url');
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['form_id', 'occurred_at']);
            $table->index(['visitor_id', 'form_id']);
        });

        Schema::create('form_lead_scoring_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('form_id');
            $table->string('name');
            $table->json('rules');
            $table->integer('threshold_for_qualified')->default(50);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['form_id']);
        });

        // Add V4 columns to form_submissions (V3 table)
        if (Schema::hasTable('form_submissions')) {
            Schema::table('form_submissions', function (Blueprint $table) {
                $table->integer('lead_score')->nullable()->after('data');
                $table->json('lead_score_breakdown')->nullable()->after('lead_score');
                $table->json('attribution')->nullable()->after('lead_score_breakdown');
                $table->json('conversion_path')->nullable()->after('attribution');
                $table->boolean('is_qualified')->default(false)->after('conversion_path');
                $table->uuid('assigned_to')->nullable()->after('is_qualified');
                $table->timestamp('assigned_at')->nullable()->after('assigned_to');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('form_submissions')) {
            Schema::table('form_submissions', function (Blueprint $table) {
                $table->dropColumn([
                    'lead_score', 'lead_score_breakdown', 'attribution',
                    'conversion_path', 'is_qualified', 'assigned_to', 'assigned_at',
                ]);
            });
        }
        Schema::dropIfExists('form_lead_scoring_rules');
        Schema::dropIfExists('form_analytics_events');
    }
};
