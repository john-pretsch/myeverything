<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('interview_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('interview_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('interview_category_id')->constrained('interview_categories')->cascadeOnDelete();
            $table->text('question');
            $table->longText('answer')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'interview_category_id']);
        });

        $now = now();
        $insert = fn (string $name, string $slug, int $position, ?int $parentId = null) => DB::table('interview_categories')->insertGetId([
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'position' => $position,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $insert('Behavioral', 'behavioral', 0);
        $technicalId = $insert('Technical', 'technical', 1);
        $insert('General', 'general', 2);
        $insert('Laravel', 'laravel', 0, $technicalId);
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_questions');
        Schema::dropIfExists('interview_categories');
    }
};
