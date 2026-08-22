<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            // Null = parsing failed or hasn't run; '' is not a valid state here
            // since even a sparse resume yields some text once parsing succeeds.
            $table->longText('content')->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->dropColumn('content');
        });
    }
};
