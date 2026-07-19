<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_verification_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('domain_id');
            $table->string('verification_type')->default('txt');
            $table->string('record_name');
            $table->string('record_value', 64);
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(50);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->foreign('domain_id')->references('id')->on('domains')->cascadeOnDelete();
            $table->index(['status', 'next_attempt_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_verification_jobs');
    }
};
