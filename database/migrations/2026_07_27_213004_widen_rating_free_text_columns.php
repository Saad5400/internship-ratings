<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Free-text fields validated up to 500 chars overflowed their
     * varchar(255) columns on PostgreSQL, so widen them to text.
     */
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->text('pros')->nullable()->change();
            $table->text('cons')->nullable()->change();
            $table->text('application_method')->nullable()->change();
            $table->text('contact_method')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->string('pros')->nullable()->change();
            $table->string('cons')->nullable()->change();
            $table->string('application_method')->nullable()->change();
            $table->string('contact_method')->nullable()->change();
        });
    }
};
