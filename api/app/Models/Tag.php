<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class Tag extends Model
{
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(NewsArticle::class, 'news_article_tag')
            ->withPivot('applied_by_user_id')
            ->withTimestamps();
    }
}
