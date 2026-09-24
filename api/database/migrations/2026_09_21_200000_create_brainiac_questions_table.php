<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brainiac_questions', function (Blueprint $table) {
            $table->id();
            $table->string('subsection')->default('pi_cognitive');
            $table->enum('category', ['numerical', 'verbal', 'abstract']);
            $table->text('prompt');
            $table->json('options');
            $table->unsignedTinyInteger('correct_option');
            $table->text('explanation')->nullable();
            $table->unsignedTinyInteger('difficulty')->nullable();
            $table->timestamps();

            $table->index(['subsection', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brainiac_questions');
    }
};
