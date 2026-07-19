<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saml_identity_providers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('entity_id');
            $table->longText('metadata_xml');
            $table->string('sso_url');
            $table->string('slo_url')->nullable();
            $table->text('x509_certificate');
            $table->json('attribute_mapping')->nullable();
            $table->json('role_mapping')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('saml_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('user_id')->nullable();
            $table->string('request_id');
            $table->string('relay_state')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saml_sessions');
        Schema::dropIfExists('saml_identity_providers');
    }
};
