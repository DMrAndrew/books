<?php
declare(strict_types=1);

namespace Books\Blog\Classes\Services;

use Books\Blog\Classes\Enums\PostStatus;
use Books\Blog\Models\Post;
use Carbon\Carbon;
use DOMDocument;
use Event;
use Exception;
use System\Models\File;

class PostService
{
    /**
     * @return void
     */
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

    /**
     * @param Post $post
     *
     * @return void
     * @throws Exception
     */
    public static function replaceBase64ImagesWithFiles(Post $post): void
    {
        /**
         * Replace base64 images to files
         */
        $HTMLContent = $post->content;

        $doc = new DOMDocument();
        @$doc->loadHTML($HTMLContent);

        $imageTags = $doc->getElementsByTagName('img');
        foreach ($imageTags as $key => $tag) {
            $src = $tag->getAttribute('src');

            if (strlen($src) === 0) {
                continue;
            }

            /**
             * Is src data valid base64 string
             */
            if (str_contains($src, 'data:') && str_contains($src, ';base64')) {
                /**
                 * Separate base64 data from mimetype
                 */
                $base64Data = explode(',', $src);
                if (is_array($base64Data)
                    && base64_encode(base64_decode($base64Data[1], true)) === $base64Data[1]) {

                    /** base64 image extension */
                    $extension = explode('/', mime_content_type($src))[1];
                    $outputFile = time() . '.' . $extension;

                    /**
                     * todo добавить валидацию расширения файла
                     * todo добавить валидацию размеров загруженного файла
                     */

                    /** attach image to post model */
                    $image = (new File())->fromData(base64_decode($base64Data[1]), $outputFile);
                    $image->save();
                    $post->content_images()->add($image);

                    /** update src from base64 to file path  */
                    $filepath = $image->getPath();

                    $HTMLContent = str_replace($src, $filepath, $HTMLContent);
                } else {
                    throw new Exception("Не удается обработать загруженное изображение №{$key}");
                }
            }
        }

        $post->content = $HTMLContent;
        $post->save();

        /**
         * Delete unused or deleted images
         */
        $post->content_images->each(function ($imageFile) use ($HTMLContent) {
            if ( !str_contains($HTMLContent, $imageFile->getPath())) {
                $imageFile->delete();
            }
        });
    }
}
