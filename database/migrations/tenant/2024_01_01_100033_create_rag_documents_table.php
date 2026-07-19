<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if Postgres for pgvector support
        $isPostgres = DB::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';

        Schema::create('rag_documents', function (Blueprint $table) use ($isPostgres) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('entry_id');
            $table->string('field_handle');
            $table->integer('chunk_index')->default(0);
            $table->text('chunk_text');
            if ($isPostgres) {
                $table->vector('embedding', 1536)->nullable();
            } else {
                $table->json('embedding')->nullable();
            }
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'entry_id']);
            $table->index(['tenant_id', 'chunk_index']);
        });

        if ($isPostgres) {
            DB::statement('CREATE INDEX rag_documents_embedding_idx ON rag_documents USING hnsw (embedding vector_cosine_ops)');
        }

        Schema::create('rag_queries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('user_id')->nullable();
            $table->text('query_text');
            $table->json('retrieved_document_ids')->nullable();
            $table->text('answer_text')->nullable();
            $table->string('model_used')->nullable();
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('latency_ms')->default(0);
            $table->string('feedback_rating')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rag_queries');
        Schema::dropIfExists('rag_documents');
    }
};
