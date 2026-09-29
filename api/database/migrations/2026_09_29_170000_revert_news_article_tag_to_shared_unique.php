<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Order matters, same reason as the migration that introduced the
        // per-user index: MySQL needs a supporting index on
        // news_article_id for its foreign key at all times.
        Schema::table('news_article_tag', function (Blueprint $table) {
            $table->unique(['news_article_id', 'tag_id']);
        });

        Schema::table('news_article_tag', function (Blueprint $table) {
            $table->dropUnique('news_article_tag_per_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('news_article_tag', function (Blueprint $table) {
            $table->unique(['news_article_id', 'tag_id', 'applied_by_user_id'], 'news_article_tag_per_user_unique');
        });

        Schema::table('news_article_tag', function (Blueprint $table) {
            $table->dropUnique(['news_article_id', 'tag_id']);
        });
    }
};
