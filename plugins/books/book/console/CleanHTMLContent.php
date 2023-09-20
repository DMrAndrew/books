<?php namespace Books\Book\Console;

use Books\Book\Classes\PromocodeGenerationLimiter;
use Books\Book\Models\Promocode;
use Books\Profile\Models\Profile;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * CleanHTMLContent Command
 *
 * @link https://docs.octobercms.com/3.x/extend/console-commands.html
 */
class CleanHTMLContent extends Command
{
    /**
     * @var string signature for the console command.
     */
    protected $signature = 'book:content:clean_html
                            {--type= : type of record}
                            {--id= : id(s) of records}';

    /**
     * @var string description is the console command description
     */
    protected $description = 'Чистка html контента. Удаление лишних тегов, аттрибутов, стилей, --type = объект (book_content, book_annotation, blog_post, author_profile), --id = список ID записей (через запятую)';

    /**
     * handle executes the console command.
     */
    public function handle()
    {
        $this->output->writeln("Очистка html контента");

        //todo

        return;
    }
}
