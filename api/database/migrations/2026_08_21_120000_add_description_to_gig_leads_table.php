<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gig_leads', function (Blueprint $table) {
            // Null = not fetched yet, '' = fetched but nothing found, otherwise the parsed text.
            $table->text('description')->nullable()->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('gig_leads', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
