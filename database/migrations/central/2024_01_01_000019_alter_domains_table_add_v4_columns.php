<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V4 ALTER: Adds ~15 columns to the V3 domains table.
 * All new columns are nullable to maintain V3 backward compatibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->boolean('is_wildcard')->default(false)->after('is_primary');
            $table->string('wildcard_parent')->nullable()->after('is_wildcard');
            $table->uuid('ssl_certificate_id')->nullable()->after('ssl_status');
            $table->timestamp('ssl_expires_at')->nullable()->after('ssl_certificate_id');
            $table->string('dns_verification_status')->default('unverified')->after('ssl_expires_at');
            $table->string('dns_verification_token', 64)->nullable()->after('dns_verification_status');
            $table->timestamp('dns_verified_at')->nullable()->after('dns_verification_token');
            $table->uuid('theme_id')->nullable()->after('dns_verified_at');
            $table->uuid('site_id')->nullable()->after('theme_id');
            $table->string('default_collection_handle')->nullable()->after('site_id');
            $table->string('route_prefix')->nullable()->after('default_collection_handle');
            $table->json('config')->nullable()->after('route_prefix');
            $table->string('status')->default('active')->after('config');
            $table->string('redirect_target')->nullable()->after('status');
            $table->string('analytics_property_id')->nullable()->after('redirect_target');
            $table->timestamp('last_request_at')->nullable()->after('analytics_property_id');

            $table->foreign('ssl_certificate_id')->references('id')->on('ssl_certificates')->nullOnDelete();
            $table->foreign('theme_id')->references('id')->on('themes')->nullOnDelete();
            $table->index('is_wildcard');
            $table->index('dns_verification_status');
            $table->index('ssl_expires_at');
            $table->index('status');
        });

        // Mark all existing V3 domains as verified for backward compatibility
        DB::table('domains')->whereNull('dns_verified_at')->update([
            'dns_verification_status' => 'verified',
            'dns_verified_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropForeign(['ssl_certificate_id']);
            $table->dropForeign(['theme_id']);
            $table->dropIndex(['is_wildcard']);
            $table->dropIndex(['dns_verification_status']);
            $table->dropIndex(['ssl_expires_at']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'is_wildcard', 'wildcard_parent', 'ssl_certificate_id', 'ssl_expires_at',
                'dns_verification_status', 'dns_verification_token', 'dns_verified_at',
                'theme_id', 'site_id', 'default_collection_handle', 'route_prefix',
                'config', 'status', 'redirect_target', 'analytics_property_id',
                'last_request_at',
            ]);
        });
    }
};
