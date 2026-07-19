<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->uuid('plan_id')->nullable();
            $table->string('status')->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->json('data')->nullable();
            $table->uuid('current_theme_id')->nullable();
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('billing_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
