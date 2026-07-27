<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records whether a company's website actually has a favicon.
 *
 * The favicon CDN never 404s: asked about a host with no icon it answers 200
 * with a generic grey globe. The avatar therefore cannot tell "no logo" from
 * "logo" — its `onerror` fallback to the company initial never fires — so a
 * company whose site has no icon renders as an anonymous globe instead of its
 * own letter. Nothing about that is derivable from the website string, so it
 * has to be measured once and stored.
 *
 * Three states, and the null matters: `null` means "never probed" and keeps the
 * previous optimistic behaviour, `true` means a real icon was served, `false`
 * means the CDN returned its placeholder and the avatar shows the initial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('has_logo')->nullable()->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('has_logo');
        });
    }
};
