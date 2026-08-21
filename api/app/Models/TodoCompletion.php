<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['todo_id', 'completed_on'])]
class TodoCompletion extends Model
{
    /** @use HasFactory<\Database\Factories\TodoCompletionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'completed_on' => 'date',
        ];
    }

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }
}
