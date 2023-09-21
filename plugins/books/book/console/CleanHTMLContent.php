<?php namespace Books\Book\Console;

use Books\Book\Classes\Services\TextCleanerService;
use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Books\Book\Models\Content;
use Illuminate\Console\Command;
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
    protected $description = 'Чистка html контента. Удаление лишних тегов, аттрибутов, стилей, --type = объект (book_content, book_annotation, blog_post, author_profile), --id = список ID записей (через запятую)';

    protected function promptForMissingArgumentsUsing()
    {
        return [
            'type' => 'Какой класс чистим? (book_content, book_annotation, blog_post, author_profile)',
            'ids' => 'Список ID записей (через запятую)',
        ];
    }

    /**
     * handle executes the console command.
     */
    public function handle()
    {
        $this->output->writeln("Очистка html контента..");

        $type = $this->argument('type');
        $idsList = $this->argument('ids');
        $ids = explode(',', $idsList);

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
                $book->ebook?->chapters?->each(function($chapter) {
                    try{
                        $this->info(" --Чистка главы [{$chapter->id}] `{$chapter->title}`");
                        $chapter->content->update([
                            'body' => TextCleanerService::cleanContent($chapter->content->body)
                        ]);

                    } catch(Throwable $ignored){
                        $this->error($ignored->getMessage());
                    }

                    /**
                     * Чистим пагинацию
                     */
                    dd($chapter->pagination);
//                    try{
//                        $this->info(" --Чистка пагинации [{$chapter->pagination->id}]");
//                        $chapter->pagination->content->update([
//                            'body' => TextCleanerService::cleanContent($chapter->pagination->content->body)
//                        ]);
//
//                    } catch(Throwable $ignored){
//                        $this->error($ignored->getMessage());
//                    }
                });


//                $book->ebook?->paginations?->each(function($pagination) {
//                    $this->info(" --Чистка пагинации [{$pagination->id}]");
//
//                    try{
//                        $pagination->content->update([
//                            'body' => TextCleanerService::cleanContent($pagination->content->body)
//                        ]);
//
//                    } catch(Throwable $ignored){
//                        $this->error($ignored->getMessage());
//                    }
//                });
            }
        }

        //dd($type);

        return;
    }
}
