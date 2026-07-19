<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant user invitations — required for secure SSO bridge.
 *
 * Allows an Owner/Admin of a tenant to invite a user by email before they
 * can join via SSO. Without a matching invitation, an existing user from
 * another tenant cannot be silently attached to a new tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_user_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('email');
            $table->uuid('user_id')->nullable(); // null until accepted
            $table->string('role')->default('viewer');
            $table->uuid('invited_by')->nullable();
            $table->string('token', 64)->unique(); // invitation token
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('invited_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['tenant_id', 'email', 'accepted_at']);
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user_invitations');
    }
};
