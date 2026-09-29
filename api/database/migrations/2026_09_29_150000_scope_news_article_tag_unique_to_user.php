<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Order matters: MySQL requires a supporting index on news_article_id
        // for its foreign key at all times, so the new unique index (which
        // also starts with news_article_id) must be added before the old
        // one is dropped, not after.
        Schema::table('news_article_tag', function (Blueprint $table) {
            $table->unique(['news_article_id', 'tag_id', 'applied_by_user_id'], 'news_article_tag_per_user_unique');
        });

        Schema::table('news_article_tag', function (Blueprint $table) {
            $table->dropUnique(['news_article_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::table('news_article_tag', function (Blueprint $table) {
            $table->unique(['news_article_id', 'tag_id']);
        });

        Schema::table('news_article_tag', function (Blueprint $table) {
            $table->dropUnique('news_article_tag_per_user_unique');
        });
    }
};
