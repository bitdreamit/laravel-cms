<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scim_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('token_hash');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index('token_hash');
        });

        Schema::create('audit_streams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('destination_type');
            $table->json('destination_config');
            $table->json('event_filter')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_delivery_at')->nullable();
            $table->string('last_delivery_status')->nullable();
            $table->text('last_delivery_error')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('audit_stream_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('audit_stream_id');
            $table->uuid('activity_log_id')->nullable();
            $table->json('payload');
            $table->integer('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->integer('attempts')->default(0);
            $table->string('status')->default('pending');
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('audit_stream_id')->references('id')->on('audit_streams')->cascadeOnDelete();
            $table->index(['audit_stream_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_stream_deliveries');
        Schema::dropIfExists('audit_streams');
        Schema::dropIfExists('scim_tokens');
    }
};
