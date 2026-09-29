<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class Topic extends Model
{
    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(NewsSource::class, 'news_source_topic')
            ->withTimestamps();
    }
}
