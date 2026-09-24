<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brainiac_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subsection')->default('pi_cognitive');
            $table->unsignedInteger('total_questions');
            $table->unsignedInteger('time_limit_seconds');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('score')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'subsection']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brainiac_attempts');
    }
};
