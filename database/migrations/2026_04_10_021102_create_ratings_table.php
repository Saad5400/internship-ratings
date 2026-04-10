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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Role & location
            $table->string('role_title');
            $table->string('department')->nullable();
            $table->string('city')->nullable();
            $table->unsignedTinyInteger('duration_months');
            $table->string('sector')->nullable();   // government | private | nonprofit | other
            $table->string('modality');             // onsite | hybrid | remote

            // Concrete facts students actually ask about
            $table->unsignedInteger('stipend_sar')->nullable();  // null = unpaid / not disclosed
            $table->boolean('had_supervisor')->nullable();
            $table->boolean('mixed_env')->nullable();
            $table->boolean('job_offer')->nullable();

            // Scores — multi-criteria (1-5 each) + overall
            $table->unsignedTinyInteger('rating_mentorship');
            $table->unsignedTinyInteger('rating_learning');
            $table->unsignedTinyInteger('rating_culture');
            $table->unsignedTinyInteger('rating_compensation');
            $table->unsignedTinyInteger('overall_rating');
            $table->string('recommendation');       // yes | maybe | no

            // Experience
            $table->text('review_text');
            $table->string('pros')->nullable();
            $table->string('cons')->nullable();

            // Author
            $table->string('reviewer_name')->nullable();
            $table->string('reviewer_major')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
