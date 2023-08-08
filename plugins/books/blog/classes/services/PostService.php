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
                $base64MimeType = $base64Data[0] ?? null;
                $base64Content = $base64Data[1] ?? null;

                if ($base64MimeType && $base64Content
                    && base64_encode(base64_decode($base64Content, true)) === $base64Content) {

                    /** base64 image extension */
                    $extensionData = explode('/', mime_content_type($src));
                    if ( !is_array($extensionData) || !isset($extensionData[1])) {
                        throw new Exception("Не удается обработать расширение загруженного изображения №{$key}");
                    }
                    $extension = $extensionData[1];

                    /**
                     * Validate image
                     */
                    if ( !in_array($extension, Post::AVAILABLE_IMAGE_EXTENSIONS)) {
                        throw new Exception("Недопустимый тип файла. Разрешенные расширения для изображений: " . implode(', ', Post::AVAILABLE_IMAGE_EXTENSIONS));
                    }
                    $maxImageSize = Post::MAX_IMAGE_SIZE_MB * 1024 * 1024;
                    $base64ContentSize = self::getBase64ImageSize($src);
                    if ($base64ContentSize > $maxImageSize) {
                        throw new Exception("Максимальный размер загружаемого изображения составляет: " . Post::MAX_IMAGE_SIZE_MB . ' Мб');
                    }

                    /**
                     * Attach image to post model
                     */
                    $outputFile = time() . '.' . $extension;
                    $image = (new File())->fromData(base64_decode($base64Data[1]), $outputFile);
                    $image->save();
                    $post->content_images()->add($image);

                    /**
                     * Update src from base64 to file path
                     */
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

    /**
     * Returns size in bytes
     * @param $base64Image
     *
     * @return int
     */
    public static function getBase64ImageSize($base64Image): int
    {
        return (int) (strlen(rtrim($base64Image, '=')) * 3 / 4);
    }
}
