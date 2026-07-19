<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('common_name');
            $table->json('san_domains')->nullable();
            $table->boolean('is_wildcard')->default(false);
            $table->string('provider')->default('letsencrypt');
            $table->text('certificate_pem');
            $table->text('private_key_pem');
            $table->text('chain_pem')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('last_renewal_attempt')->nullable();
            $table->integer('renewal_failure_count')->default(0);
            $table->uuid('acme_account_id')->nullable();
            $table->string('challenge_type')->default('http-01');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('acme_account_id')->references('id')->on('acme_accounts')->nullOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssl_certificates');
    }
};
