<?php namespace Books\Book\Console;

use Books\Blog\Models\Post;
use Books\Book\Classes\Services\TextCleanerService;
use Books\Book\Models\Book;
use Books\Profile\Models\Profile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * CleanHTMLContent Command
 * ex: php artisan book:content:clean_html book_content 10,20,55
 *
 * @link https://docs.octobercms.com/3.x/extend/console-commands.html
 */
class CleanHTMLContent extends Command
{
    /**
     * @var string signature for the console command.
     */
    protected $signature = 'book:content:clean_html
                            {type : type of record}
                            {ids : id(s) of records}';

    /**
     * @var string description is the console command description
     */
    protected $description = 'Чистка html контента. Удаление лишних тегов, аттрибутов, стилей, --type = объект (book_content, book_annotation, blog_post, author_about), --id = список ID записей (через запятую)';

    /**
     * handle executes the console command.
     */
    public function handle()
    {
        $this->output->writeln("Очистка html контента..");

        $type = $this->argument('type');
        $idsList = $this->argument('ids');
        $ids = explode(',', $idsList);

        $csvName = storage_path() . '/' . now()->format('Y-m-d_H-i-s') . '.csv';

        /**
         * Чистка контента в книгах
         */
        if ($type == 'book_content') {

            $this->warn('Чистка контента в книгах');

            foreach($ids as $bookId) {
                $book = Book
                    ::with('ebook', 'ebook.chapters', 'ebook.chapters.pagination')
                    ->where('id', $bookId)
                    ->first();

                if (!$book) {
                    $this->error("Книга с id [{$bookId}] не найдена");
                    continue;
                }

                $this->info("Чистка книги [{$bookId}] `{$book->title}`");

                /**
                 * Чистим главы
                 */
                $book->ebook?->chapters?->each(function($chapter) use ($csvName) {
                    try{
                        $this->info(" --Чистка главы [{$chapter->id}] `{$chapter->title}`");
                        $chapter->content->update([
                            'body' => TextCleanerService::cleanContent($chapter->content->body)
                        ]);

                    } catch(Throwable $ignored){
                        $this->logToCSV($csvName, [[$ignored->getMessage()]]);
                        $this->error($ignored->getMessage());
                    }

                    /**
                     * Чистим пагинацию
                     */
                    $chapter->pagination?->each(function($pagination) use ($csvName) {
                        try {
                            $this->info(" -- --Чистка пагинации [{$pagination->id}]");
                            $pagination->content->update([
                                'body' => TextCleanerService::cleanContent($pagination->content->body)
                            ]);

                        } catch (Throwable $ignored) {
                            $this->logToCSV($csvName, [[$ignored->getMessage()]]);
                            $this->error($ignored->getMessage());
                        }
                    });
                });
            }
        }

        /**
         * Чистка контента в аннотации книг
         */
        else if ($type == 'book_annotation') {
            $this->warn('Чистка аннотаций в книгах');

            foreach($ids as $bookId) {
                $book = Book
                    ::with('ebook')
                    ->where('id', $bookId)
                    ->first();

                if (!$book) {
                    $this->error("Книга с id [{$bookId}] не найдена");
                    continue;
                }

                try {
                    $this->info("Чистка аннотации книги [{$bookId}] `{$book->title}`");

                    if ($book->annotation) {
                        $book->update([
                            'annotation' => TextCleanerService::cleanContent($book->annotation)
                        ]);
                    }
                } catch (Throwable $ignored) {
                    $this->logToCSV($csvName, [[$ignored->getMessage()]]);
                    $this->error($ignored->getMessage());
                }
            }
        }

        /**
         * Чистка описания автора/профиля
         */
        else if ($type == 'author_about') {
            $this->warn('Чистка описания автора/профиля');

            foreach($ids as $profileId) {
                $profile = Profile
                    ::where('id', $profileId)
                    ->first();

                if (!$profile) {
                    $this->error("Автор/Профиль с id [{$profileId}] не найден");
                    continue;
                }

                try {
                    $this->info("Чистка описания профиля [{$profileId}] `{$profile->username}`");

                    if ($profile->about) {

                        $profile->update([
                            'about' => TextCleanerService::cleanContent($profile->about)
                        ]);
                    }
                } catch (Throwable $ignored) {
                    $this->logToCSV($csvName, [[$ignored->getMessage()]]);
                    $this->error($ignored->getMessage());
                }
            }
        }

        /**
         * Чистка контента публикации блога
         */
        else if ($type == 'blog_post') {
            $this->warn('Чистка контента публикации блога');

            foreach($ids as $postId) {
                $blogPost = Post
                    ::where('id', $postId)
                    ->first();

                if (!$blogPost) {
                    $this->error("Публикация с id [{$postId}] не найдена");
                    continue;
                }

                try {
                    $this->info("Чистка публикации [{$postId}] `{$blogPost->title}`");

                    if ($blogPost->content) {

                        $blogPost->update([
                            'content' => TextCleanerService::cleanContent($blogPost->content)
                        ]);
                    }
                } catch (Throwable $ignored) {
                    $this->logToCSV($csvName, [[$ignored->getMessage()]]);
                    $this->error($ignored->getMessage());
                }
            }
        }

        else {
            $this->error("`{$type}` - Неизвестный тип модели для чистки HTML контента. Доступные варианты `type`: book_content, book_annotation, blog_post, author_about");
        }

        $this->warn('Лог чистки записан в файл ' . $csvName);

        return;
    }

    /**
     * @param string $fileName
     * @param array $list
     *
     * @return void
     */
    function logToCSV(string $fileName, array $list): void
    {
        $fp = fopen($fileName, 'w');

        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
    }
}
