<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\ActivityLoggerService;

class PostObserver
{
    public function created(Post $post): void
    {
        ActivityLoggerService::log($post, 'created', $post->toArray());
    }

    public function updated(Post $post): void
    {
        // getDirty() returns only the changed attributes — keeps payloads small
        ActivityLoggerService::log($post, 'updated', $post->getDirty());
    }

    public function deleted(Post $post): void
    {
        ActivityLoggerService::log($post, 'deleted', $post->toArray());
    }
}
