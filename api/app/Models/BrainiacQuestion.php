<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['subsection', 'category', 'prompt', 'options', 'correct_option', 'explanation', 'difficulty'])]
class BrainiacQuestion extends Model
{
    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }
}
