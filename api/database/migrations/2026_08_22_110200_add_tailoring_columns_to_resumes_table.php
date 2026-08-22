<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')
                ->constrained()->nullOnDelete();
            // The lead this resume was tailored for, and the resume it was
            // tailored from. Both null for an originally-uploaded resume.
            $table->foreignId('gig_lead_id')->nullable()->after('organization_id')
                ->constrained()->nullOnDelete();
            $table->foreignId('source_resume_id')->nullable()->after('gig_lead_id')
                ->constrained('resumes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_resume_id');
            $table->dropConstrainedForeignId('gig_lead_id');
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
