<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_source_id')->constrained()->cascadeOnDelete();
            $table->string('guid');
            $table->string('title');
            $table->string('url', 2048);
            $table->text('summary')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['news_source_id', 'guid']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_articles');
    }
};
