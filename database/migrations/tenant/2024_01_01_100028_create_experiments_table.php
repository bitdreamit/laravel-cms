<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('handle');
            $table->text('description')->nullable();
            $table->string('experiment_type');
            $table->uuid('entry_id')->nullable();
            $table->string('collection_handle')->nullable();
            $table->string('status')->default('draft');
            $table->integer('traffic_allocation')->default(100);
            $table->uuid('winning_variant_id')->nullable();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->string('goal_type');
            $table->json('goal_config')->nullable();
            $table->integer('min_sample_size')->default(100);
            $table->decimal('confidence_threshold', 4, 3)->default(0.950);
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'handle']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('experiment_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('experiment_id');
            $table->string('name');
            $table->string('handle');
            $table->boolean('is_control')->default(false);
            $table->integer('weight')->default(50);
            $table->uuid('entry_id')->nullable();
            $table->string('template_override')->nullable();
            $table->json('field_overrides')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('experiment_id')->references('id')->on('experiments')->cascadeOnDelete();
            $table->index(['experiment_id', 'is_control']);
        });

        Schema::create('experiment_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('experiment_id');
            $table->uuid('variant_id');
            $table->string('visitor_id', 36);
            $table->uuid('user_id')->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('converted_at')->nullable();
            $table->decimal('conversion_value', 12, 2)->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('experiment_id')->references('id')->on('experiments')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('experiment_variants')->cascadeOnDelete();
            $table->unique(['experiment_id', 'visitor_id']);
            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiment_assignments');
        Schema::dropIfExists('experiment_variants');
        Schema::dropIfExists('experiments');
    }
};
