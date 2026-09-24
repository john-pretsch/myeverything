<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brainiac_attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('brainiac_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('brainiac_questions')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->unsignedTinyInteger('selected_option')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
            $table->index(['attempt_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brainiac_attempt_questions');
    }
};
