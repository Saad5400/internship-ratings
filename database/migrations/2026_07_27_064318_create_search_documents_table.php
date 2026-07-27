<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per (record, field) — the unit both search legs score.
     *
     * `content` carries the normalized text the lexical leg matches against;
     * `embedding` carries the vector the semantic leg compares. `content_hash`
     * lets re-indexing skip fields whose text has not changed, so a re-index
     * stays cheap even though embedding is a paid network call.
     *
     * The record is polymorphic so ratings and users can be indexed into the
     * same table when the admin panel plugs in.
     */
    public function up(): void
    {
        Schema::create('search_documents', function (Blueprint $table) {
            $table->id();
            $table->string('searchable_type');
            $table->unsignedBigInteger('searchable_id');
            $table->string('field');

            $table->text('content');
            $table->string('content_hash', 64);

            // Base64 of packed float32 — see App\Support\Search\Vector for why
            // this is a text column rather than a binary one.
            $table->text('embedding')->nullable();
            $table->string('embedding_model')->nullable();
            $table->unsignedSmallInteger('dimensions')->nullable();
            $table->timestamp('indexed_at')->nullable();

            $table->timestamps();

            $table->unique(['searchable_type', 'searchable_id', 'field']);
            $table->index(['searchable_type', 'field']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_documents');
    }
};
