<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collab_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('entry_id');
            $table->string('field_handle', 100);
            $table->binary('yjs_document_state')->nullable();
            $table->timestamp('last_active_at')->useCurrent();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'entry_id', 'field_handle']);
            $table->index('last_active_at');
        });

        Schema::create('collab_presence', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('collab_session_id');
            $table->uuid('user_id');
            $table->json('cursor_position')->nullable();
            $table->string('selection_color')->default('#3b82f6');
            $table->timestamp('last_heartbeat_at')->useCurrent();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('collab_session_id')->references('id')->on('collab_sessions')->cascadeOnDelete();
            $table->index(['collab_session_id', 'last_heartbeat_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collab_presence');
        Schema::dropIfExists('collab_sessions');
    }
};
