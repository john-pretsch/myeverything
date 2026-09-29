<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'summary' => $this->summary,
            'image_url' => $this->image_url,
            'published_at' => $this->published_at?->toIso8601String(),
            'source' => [
                'id' => $this->source->id,
                'name' => $this->source->name,
                'topics' => TopicResource::collection($this->source->topics),
            ],
            'tags' => $this->article_tags ?? [],
        ];
    }
}
