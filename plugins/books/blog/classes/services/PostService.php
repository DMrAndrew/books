<?php
declare(strict_types=1);

namespace Books\Blog\Classes\Services;

use Books\Blog\Classes\Enums\PostStatus;
use Books\Blog\Models\Post;
use Carbon\Carbon;
use Event;

class PostService
{
    public static function delayedPublications(): void
    {
        Post::query()
            ->planned()
            ->where('published_at', '<=', Carbon::now())
            ->lockForUpdate()
            ->get()
            ->map(function ($post) {
                $post->fill([
                    'status' => PostStatus::PUBLISHED,
                    'published_at' => Carbon::now(),
                ]);
                $post->save();

                return fn() => Event::fire('blog.post.published', [$post]);
            });
    }
}
