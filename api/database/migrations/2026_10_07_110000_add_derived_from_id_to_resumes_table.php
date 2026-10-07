<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            // The resume this one is a PDF/HTML conversion of (distinct from
            // source_resume_id, which means "tailored from").
            $table->foreignId('derived_from_id')->nullable()->after('source_resume_id')
                ->constrained('resumes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('derived_from_id');
        });
    }
};
