<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('segments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('handle');
            $table->text('description')->nullable();
            $table->json('rules');
            $table->boolean('is_dynamic')->default(true);
            $table->integer('estimated_size')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'handle']);
        });

        Schema::create('segment_visitors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('segment_id');
            $table->string('visitor_id', 36);
            $table->uuid('user_id')->nullable();
            $table->timestamp('matched_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('segment_id')->references('id')->on('segments')->cascadeOnDelete();
            $table->index(['segment_id', 'visitor_id']);
        });

        Schema::create('personalization_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('handle');
            $table->uuid('segment_id');
            $table->string('target_type');
            $table->json('target_config');
            $table->integer('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('segment_id')->references('id')->on('segments')->cascadeOnDelete();
            $table->index(['tenant_id', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personalization_rules');
        Schema::dropIfExists('segment_visitors');
        Schema::dropIfExists('segments');
    }
};
