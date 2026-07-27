<?php

use App\Support\Arabic;
use App\Support\PopularSearches;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per distinct search term — an aggregate counter, never a log.
     *
     * PRIVACY CONTRACT: this table stores NO user identifiers of any kind. No
     * user id, no session id, no IP address, no user agent, no per-search
     * timestamp, no `created_at`. A search does not append a row; it increments
     * an existing counter, so the table is bounded by the number of distinct
     * terms and holds nothing that could be joined back to a person.
     *
     * `last_searched_at` is deliberately coarse — written truncated to the hour
     * by {@see PopularSearches} — so terms can age out of the
     * "recent" window without recording when any individual searched.
     *
     * `term` is the normalized form ({@see Arabic::normalize}), so
     * "الإنماء" and "الانماء" share one counter; `display_term` is the readable
     * form shown back to visitors.
     */
    public function up(): void
    {
        Schema::create('popular_searches', function (Blueprint $table) {
            $table->id();
            $table->string('term', 64)->unique();
            $table->string('display_term', 64);
            $table->unsignedInteger('search_count')->default(0);

            // Hour granularity only. See the privacy note above.
            $table->timestamp('last_searched_at')->nullable();

            // The exact shape of the "top terms" read: window by recency, then
            // order by popularity.
            $table->index(['last_searched_at', 'search_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('popular_searches');
    }
};
