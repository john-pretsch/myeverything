<?php

namespace App\Http\Controllers\News;

use App\Http\Controllers\Controller;
use App\Http\Resources\TopicResource;
use App\Models\Topic;

class TopicController extends Controller
{
    public function index()
    {
        return TopicResource::collection(Topic::orderBy('name')->get());
    }
}
