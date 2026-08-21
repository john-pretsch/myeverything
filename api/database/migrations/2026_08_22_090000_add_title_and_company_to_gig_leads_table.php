<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gig_leads', function (Blueprint $table) {
            // Null = not fetched yet, '' = fetched but not found in the JSON-LD, otherwise the parsed value.
            $table->string('title')->nullable()->after('url');
            $table->string('company')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('gig_leads', function (Blueprint $table) {
            $table->dropColumn(['title', 'company']);
        });
    }
};
