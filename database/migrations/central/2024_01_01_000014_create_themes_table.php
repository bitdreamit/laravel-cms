<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('version', 20)->default('1.0.0');
            $table->text('description')->nullable();
            $table->string('author');
            $table->string('author_url')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->string('type')->default('system');
            $table->string('screenshot_path')->nullable();
            $table->string('path');
            $table->boolean('is_active')->default(false);
            $table->json('settings_schema')->nullable();
            $table->json('supported_features')->nullable();
            $table->string('min_cms_version', 20)->nullable();
            $table->integer('installed_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('themes')->nullOnDelete();
        });

        Schema::create('theme_dependencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('theme_id');
            $table->uuid('dependency_theme_id');
            $table->string('version_constraint')->default('*');
            $table->string('type')->default('required');
            $table->timestamps();

            $table->foreign('theme_id')->references('id')->on('themes')->cascadeOnDelete();
            $table->foreign('dependency_theme_id')->references('id')->on('themes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_dependencies');
        Schema::dropIfExists('themes');
    }
};
