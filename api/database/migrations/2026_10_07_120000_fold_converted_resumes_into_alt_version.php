<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            // A resume is one entry; its converted PDF/HTML counterpart is a
            // second file on the same row, not a separate resume.
            $table->string('alt_path')->nullable()->after('path');
            $table->string('alt_mime_type')->nullable()->after('alt_path');
            $table->unsignedInteger('alt_size')->nullable()->after('alt_mime_type');
        });

        foreach (DB::table('resumes')->whereNotNull('derived_from_id')->get() as $child) {
            DB::table('resumes')->where('id', $child->derived_from_id)->update([
                'alt_path' => $child->path,
                'alt_mime_type' => $child->mime_type,
                'alt_size' => $child->size,
            ]);
            DB::table('resumes')->where('id', $child->id)->delete();
        }

        Schema::table('resumes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('derived_from_id');
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->foreignId('derived_from_id')->nullable()->after('source_resume_id')
                ->constrained('resumes')->nullOnDelete();
            $table->dropColumn(['alt_path', 'alt_mime_type', 'alt_size']);
        });
    }
};
