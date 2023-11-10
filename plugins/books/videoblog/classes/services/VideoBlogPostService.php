<?php
declare(strict_types=1);

namespace Books\Blog\Classes\Services;

use Books\Blog\Classes\Enums\PostStatus;
use Books\Blog\Models\Post;
use Books\Videoblog\Models\Videoblog;
use Carbon\Carbon;
use DOMDocument;
use Event;
use Exception;
use System\Models\File;

class VideoBlogPostService
{
    /**
     * @return void
     */
    public static function delayedPublications(): void
    {
        Videoblog::query()
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
