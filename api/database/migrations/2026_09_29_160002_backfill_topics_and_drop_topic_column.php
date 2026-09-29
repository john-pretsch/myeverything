<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $topicIds = [];
        foreach (['Local', 'Hackery', 'World'] as $name) {
            $topicIds[$name] = DB::table('topics')->insertGetId([
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $slugToName = ['local' => 'Local', 'hackery' => 'Hackery', 'world' => 'World'];

        foreach ($slugToName as $slug => $name) {
            $sourceIds = DB::table('news_sources')->where('topic', $slug)->pluck('id');

            $rows = $sourceIds->map(fn ($sourceId) => [
                'news_source_id' => $sourceId,
                'topic_id' => $topicIds[$name],
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            if (! empty($rows)) {
                DB::table('news_source_topic')->insert($rows);
            }
        }

        Schema::table('news_sources', function (Blueprint $table) {
            $table->dropIndex(['topic']);
            $table->dropColumn('topic');
        });
    }

    public function down(): void
    {
        // Best-effort/lossy, same approach as 2026_09_29_140002's drop of
        // news_article_feedback: recreate the column, don't repopulate it.
        // Do remove exactly the rows this migration created, though, so
        // rolling back and re-applying doesn't collide on the unique name.
        DB::table('topics')->whereIn('name', ['Local', 'Hackery', 'World'])->delete();

        Schema::table('news_sources', function (Blueprint $table) {
            $table->string('topic')->nullable()->after('is_default')->index();
        });
    }
};
