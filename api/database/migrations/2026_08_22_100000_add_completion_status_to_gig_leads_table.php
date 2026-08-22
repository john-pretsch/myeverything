<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gig_leads', function (Blueprint $table) {
            $table->enum('completion_status', ['started', 'complete', 'applied'])
                ->default('started')
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('gig_leads', function (Blueprint $table) {
            $table->dropColumn('completion_status');
        });
    }
};
