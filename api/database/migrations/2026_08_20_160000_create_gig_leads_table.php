<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gig_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->enum('country', ['usa', 'canada']);
            $table->enum('job_type', [
                'full_time',
                'part_time',
                'short_term_contract',
                'long_term_contract',
            ]);
            $table->enum('origin', ['linkedin', 'arc', 'indeed', 'gunio', 'other']);
            $table->enum('status', ['new', 'reviewed', 'dismissed'])->default('new');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gig_leads');
    }
};
