<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rating_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rating_id')->constrained()->cascadeOnDelete();
            $table->string('voter_hash', 64);
            $table->timestamps();

            $table->unique(['rating_id', 'voter_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rating_votes');
    }
};
