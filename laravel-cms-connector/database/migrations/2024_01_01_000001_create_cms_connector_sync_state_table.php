<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cms_connector_sync_state', function (Blueprint $table) {
            $table->id();
            $table->string('syncable_type');
            $table->unsignedBigInteger('syncable_id');
            $table->uuid('cms_entry_id')->nullable();
            $table->string('cms_entry_slug')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_sync_direction')->nullable();
            $table->string('last_sync_status')->nullable();
            $table->json('conflict_data')->nullable();
            $table->timestamps();
            $table->unique(['syncable_type', 'syncable_id']);
            $table->index('cms_entry_slug');
        });
    }
    public function down(): void { Schema::dropIfExists('cms_connector_sync_state'); }
};
