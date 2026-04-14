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
            $table->string('role_title')->nullable();
            $table->string('department')->nullable();
            $table->string('city')->nullable();
            $table->unsignedTinyInteger('duration_months')->nullable();
            $table->string('modality');             // Modality enum: onsite | hybrid | remote

            // Concrete facts students actually ask about
            $table->unsignedInteger('stipend_sar')->nullable();  // null = unpaid / not disclosed
            $table->boolean('had_supervisor')->nullable();
            $table->boolean('mixed_env')->nullable();
            $table->boolean('job_offer')->nullable();

            // Scores — multi-criteria (1-5 each) + overall
            $table->unsignedTinyInteger('rating_mentorship');
            $table->unsignedTinyInteger('rating_learning');
            $table->unsignedTinyInteger('rating_real_work');
            $table->unsignedTinyInteger('rating_team_environment');
            $table->unsignedTinyInteger('rating_organization');
            $table->decimal('overall_rating', 3, 1);
            $table->string('recommendation');       // Recommendation enum: yes | maybe | no

            // Experience
            $table->text('review_text');
            $table->string('pros')->nullable();
            $table->string('cons')->nullable();

            // Author
            $table->string('reviewer_name')->nullable();
            $table->string('reviewer_university')->nullable();
            $table->string('reviewer_college')->nullable();
            $table->string('reviewer_major')->nullable();
            $table->string('reviewer_degree')->nullable();  // بكالوريوس | ماجستير | دكتوراه | دبلوم

            // Application
            $table->string('application_method')->nullable();  // how they applied

            // Helping others
            $table->boolean('willing_to_help')->nullable();
            $table->string('contact_method')->nullable();

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
