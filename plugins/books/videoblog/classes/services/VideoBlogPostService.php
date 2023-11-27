<?php
declare(strict_types=1);

namespace Books\Videoblog\Classes\Services;

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

    public static function getYoutubeEmbedCode(Videoblog $videoblog)
    {
        $shortUrlRegex = '/youtu.be\/([a-zA-Z0-9_-]+)\??/i';
        $longUrlRegex = '/youtube.com\/((?:embed)|(?:watch))((?:\?v\=)|(?:\/))([a-zA-Z0-9_-]+)/i';

        if (preg_match($longUrlRegex, $videoblog->link, $matches)) {
            $youtube_id = $matches[count($matches) - 1];
        }

        if (preg_match($shortUrlRegex,  $videoblog->link, $matches)) {
            $youtube_id = $matches[count($matches) - 1];
        }

        $videoblog->update(['embed' => $youtube_id]);
    }
}
