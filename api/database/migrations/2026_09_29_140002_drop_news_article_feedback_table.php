<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('news_article_feedback');
    }

    public function down(): void
    {
        Schema::create('news_article_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('news_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('news_source_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('direction');
            $table->timestamps();

            $table->unique(['user_id', 'news_article_id']);
            $table->index(['user_id', 'news_source_id']);
        });
    }
};
