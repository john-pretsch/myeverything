<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsSourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'site_url' => $this->site_url,
            'feed_url' => $this->feed_url,
            'is_default' => $this->is_default,
            'topic' => $this->topic,
            'is_added' => $this->added_position !== null,
            'position' => $this->added_position,
        ];
    }
}
