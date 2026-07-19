<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('handle');
            $table->text('description')->nullable();
            $table->string('trigger_event');
            $table->json('trigger_collections')->nullable();
            $table->json('definition');
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'handle']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('workflow_id');
            $table->uuid('entry_id');
            $table->string('current_node_id');
            $table->string('status')->default('running');
            $table->json('context')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('workflow_id')->references('id')->on('workflows')->cascadeOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index('entry_id');
        });

        Schema::create('workflow_node_executions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('workflow_instance_id');
            $table->string('node_id');
            $table->string('node_type');
            $table->uuid('executed_by')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('pending');
            $table->text('comment')->nullable();
            $table->json('output')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('workflow_instance_id')->references('id')->on('workflow_instances')->cascadeOnDelete();
            $table->index(['workflow_instance_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_node_executions');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflows');
    }
};
