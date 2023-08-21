<?php namespace Books\Sitemap\Console;

use Books\Book\Models\Book;
use Books\Profile\Models\Profile;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;
use Books\Blog\Models\Post;

/**
 * GenerateSitemap Command
 *
 * @link https://docs.octobercms.com/3.x/extend/console-commands.html
 */
class GenerateSitemap extends Command
{
    const BOOKS_SITEMAP_NAME = 'sitemap_books';
    const BLOG_SITEMAP_NAME = 'sitemap_blog';
    const AUTHORS_SITEMAP_NAME = 'sitemap_authors';
    const STATIC_PAGES_SITEMAP_NAME = 'pages_sitemap';

    const SITEMAP_PAGES_LIMIT = 50000;

    /**
     * @var string signature for the console command.
     */
    protected $signature = 'sitemap:generate';

    /**
     * @var string description is the console command description
     */
    protected $description = 'Generates sitemap.xml file';

    /**
     * handle executes the console command.
     */
    public function handle()
    {
        $this->generateBooksSitemap();

        $this->generateBlogSitemap();

        $this->generateAuthorsSitemap();

        //$this->generateStaticPages();
        //$this->info($this->getSitemapFileName(self::STATIC_PAGES_SITEMAP_NAME));

        $this->generateMainSitemap();

        $this->info('Done!');

        return Command::SUCCESS;
    }

    /**
     * Книги открытые для поисковых роботов
     *  - доступные для неавторизованных пользователей
     *
     * @return void
     */
    private function generateBooksSitemap(): void
    {
        $this->warn($this->getSitemapFileName(self::BOOKS_SITEMAP_NAME));

        $sitemap = Sitemap::create();

        $pagesLimit = self::SITEMAP_PAGES_LIMIT;
        $pagesCount = 0;

        Book::public()
            ->orderBy('id', 'desc')
            /**
             * Chunking results
             */
            ->chunk(50, function (Collection $books) use ($sitemap, &$pagesCount, $pagesLimit) {

                if($pagesCount >= $pagesLimit) {
                    return false;
                }

                /**
                 * Add each page in sitemap file
                 */
                $books->each(function ($book) use ($sitemap, &$pagesCount, $pagesLimit) {

                    if($pagesCount >= $pagesLimit) {
                        return false;
                    }

                    $sitemap->add(Url::create(url('book-card', ['book_id' => $book->id]))
                        ->setLastModificationDate($book->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(1));

                    $pagesCount++;

                    return true;
                });

                return true;
            });

        $sitemap->writeToFile($this->getSitemapFilePath(self::BOOKS_SITEMAP_NAME));
    }

    /**
     * Статичные страницы
     *
     * @return void
     */
    private function generateStaticPages(): void
    {
        $staticPages = [
            route('events.list.today'),
            route('about'),
            route('privacy'),
            route('terms'),
            route('faq'),
            route('contact_support'),
        ];

        $sitemap = Sitemap::create();

        foreach ($staticPages as $page) {
            $sitemap->add(Url::create($page)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.1));
        }

        $sitemap->writeToFile($this->getSitemapFilePath(self::STATIC_PAGES_SITEMAP_NAME));
    }

    /**
     * Main Sitemap file
     *
     * @return void
     */
    private function generateMainSitemap(): void
    {
        $this->warn('sitemap.xml');

        Sitemap::create()

            ->add(Url::create($this->getSitemapFileName(self::BOOKS_SITEMAP_NAME))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(1))

            ->add(Url::create($this->getSitemapFileName(self::BLOG_SITEMAP_NAME))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(1))

            ->add(Url::create($this->getSitemapFileName(self::AUTHORS_SITEMAP_NAME))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(1))

//            ->add(Url::create($this->getSitemapFileName(self::STATIC_PAGES_SITEMAP_NAME))
//                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
//                ->setPriority(0.1))

            ->writeToFile($this->getSitemapFilePath('sitemap'));
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getSitemapFilePath(string $name): string
    {
        return public_path() . '/' . $this->getSitemapFileName($name);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getSitemapFileName(string $name): string
    {
        return $name . '.xml';
    }

    /**
     * Публикации в блоге, которые видны поисковым роботам:
     *  - в статусе Опубликован
     *  - открыты всем в настройках приватности
     *
     * @return void
     */
    private function generateBlogSitemap(): void
    {
        $this->warn($this->getSitemapFileName(self::BLOG_SITEMAP_NAME));

        $sitemap = Sitemap::create();

        $pagesLimit = self::SITEMAP_PAGES_LIMIT;
        $pagesCount = 0;

        Post
            ::publicVisible()
            ->orderBy('id', 'desc')

            /**
             * Chunking results
             */
            ->chunk(50, function (Collection $posts) use ($sitemap, &$pagesCount, $pagesLimit) {

                if($pagesCount >= $pagesLimit) {
                    return false;
                }

                /**
                 * Add each page in sitemap file
                 */
                $posts->each(function ($post) use ($sitemap, &$pagesCount, $pagesLimit) {

                    if($pagesCount >= $pagesLimit) {
                        return false;
                    }

                    $sitemap->add(Url::create(url('blog', ['post_slug' => $post->slug]))
                        ->setLastModificationDate($post->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(1));

                    $pagesCount++;

                    return true;
                });

                return true;
            });

        $sitemap->writeToFile($this->getSitemapFilePath(self::BLOG_SITEMAP_NAME));
    }

    /**
     * Страницы авторов:
     *  - пользователи, у которых есть в наличии опубликованная книга
     *
     * @return void
     */
    private function generateAuthorsSitemap(): void
    {
        $this->warn($this->getSitemapFileName(self::AUTHORS_SITEMAP_NAME));

        $sitemap = Sitemap::create();

        $pagesLimit = self::SITEMAP_PAGES_LIMIT;
        $pagesCount = 0;

        Profile
            ::booksExists()
            ->orderBy('id', 'desc')

            /**
             * Chunking results
             */
            ->chunk(50, function (Collection $authors) use ($sitemap, &$pagesCount, $pagesLimit) {

                if($pagesCount >= $pagesLimit) {
                    return false;
                }

                /**
                 * Add each page in sitemap file
                 */
                $authors->each(function ($author) use ($sitemap, &$pagesCount, $pagesLimit) {

                    if($pagesCount >= $pagesLimit) {
                        return false;
                    }

                    $sitemap->add(Url::create(url('author-page', ['author_id' => $author->id]))
                        ->setLastModificationDate($author->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(1));

                    $pagesCount++;

                    return true;
                });

                return true;
            });

        $sitemap->writeToFile($this->getSitemapFilePath(self::AUTHORS_SITEMAP_NAME));
    }
}
