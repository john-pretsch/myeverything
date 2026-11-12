<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Identity now lives only in Jepflow SSO. Every per-user table re-keys from
 * the local users.id to the SSO id (users.sso_id), then users is dropped.
 * down() cannot restore the table or the old ids: take a dump first.
 */
return new class extends Migration
{
    private const OFFSET = 1_000_000_000;

    /** @var array<string, string> table => user column */
    private const USER_COLUMNS = [
        'brainiac_attempts' => 'user_id',
        'gig_leads' => 'user_id',
        'interview_questions' => 'user_id',
        'news_article_tag' => 'applied_by_user_id',
        'news_source_user' => 'user_id',
        'organizations' => 'user_id',
        'resumes' => 'user_id',
        'tag_votes' => 'user_id',
        'todos' => 'user_id',
    ];

    public function up(): void
    {
        $users = DB::table('users')->get(['id', 'sso_id', 'preferences']);

        $unlinked = $users->whereNull('sso_id')->pluck('id');
        if ($unlinked->isNotEmpty()) {
            throw new RuntimeException('Users without an sso_id: '.$unlinked->implode(', '));
        }

        Schema::create('user_preferences', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->json('preferences')->nullable();
            $table->timestamps();
        });

        foreach ($users as $user) {
            DB::table('user_preferences')->insert([
                'user_id' => (int) $user->sso_id,
                'preferences' => $user->preferences,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (self::USER_COLUMNS as $table => $column) {
            Schema::table($table, fn (Blueprint $t) => $t->dropForeign([$column]));
        }

        // Two passes so a new id can never collide with a not-yet-moved old id
        // (or trip a unique index such as news_source_user's).
        foreach (self::USER_COLUMNS as $table => $column) {
            foreach ($users as $user) {
                DB::table($table)->where($column, $user->id)->update([$column => (int) $user->sso_id + self::OFFSET]);
            }
            DB::table($table)->where($column, '>=', self::OFFSET)->update([$column => DB::raw($column.' - '.self::OFFSET)]);
        }

        DB::table('sessions')->delete();

        Schema::dropIfExists('password_reset_tokens');
        Schema::drop('users');
    }

    public function down(): void
    {
        throw new RuntimeException('The users table cannot be restored; recover from the pre-migration dump.');
    }
};
