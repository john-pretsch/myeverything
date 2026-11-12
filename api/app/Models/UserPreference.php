<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-user settings, keyed by the Jepflow SSO user id. A row existing also
 * marks the user as already onboarded (default news sources attached).
 */
#[Fillable(['user_id', 'preferences'])]
class UserPreference extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected function casts(): array
    {
        return ['preferences' => 'array'];
    }
}
