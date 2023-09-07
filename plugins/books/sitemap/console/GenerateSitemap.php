<?php namespace Books\Sitemap\Console;

use Books\Book\Models\Book;
use Books\Catalog\Models\Genre;
use Books\Profile\Models\Profile;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use October\Rain\Database\Builder;
use Rainlab\Pages\Classes\Page;
use Spatie\Sitemap\Sitemap;
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
    const GENRES_SITEMAP_NAME = 'sitemap_categories';
    const BLOG_SITEMAP_NAME = 'sitemap_blog';
    const AUTHORS_SITEMAP_NAME = 'sitemap_authors';
    const STATIC_PAGES_SITEMAP_NAME = 'sitemap_pages';

    const PAGES_PER_ONE_SITEMAP_FILE_LIMIT = 50000;
    const DB_QUERIES_CHUNK_SIZE = 200;

    protected array $sitemapFiles = [];

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
        $this->generateGenresSitemap();
        $this->generateBlogSitemap();
        $this->generateAuthorsSitemap();
        $this->generateStaticPagesSitemap();

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

        $sitemapName = self::BOOKS_SITEMAP_NAME;

        $query = Book
            ::public()
            ->orderBy('id', 'desc');

        $this->fillSitemapWithRecords($query, $sitemapName, function ($item) {
            return url('book-card', ['book_id' => $item->id]);
        });
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

        $sitemapName = self::BLOG_SITEMAP_NAME;

        $query = Post
            ::publicVisible()
            ->orderBy('id', 'desc');

        $this->fillSitemapWithRecords($query, $sitemapName, function ($item) {
            return url('blog', ['post_slug' => $item->slug]);
        });
    }

    /**
     * @return void
     */
    private function generateGenresSitemap(): void
    {
        $this->warn($this->getSitemapFileName(self::GENRES_SITEMAP_NAME));

        $sitemapName = self::GENRES_SITEMAP_NAME;

        $query = Genre
            ::active()
            ->whereNotNull('slug')
            ->orderBy('id', 'desc');

        $this->fillSitemapWithRecords($query, $sitemapName, function ($item) {
            return url('listing', ['category_slug' => $item->slug]);
        });
    }

    /**
     * Страницы авторов:
     *  - авторы, у которых есть в наличии опубликованная книга
     *
     * @return void
     */
    private function generateAuthorsSitemap(): void
    {
        $this->warn($this->getSitemapFileName(self::AUTHORS_SITEMAP_NAME));

        $sitemapName = self::AUTHORS_SITEMAP_NAME;

        $query = Profile
            ::booksExists()
            ->orderBy('id', 'desc');

        $this->fillSitemapWithRecords($query, $sitemapName, function ($item) {
            return url('author-page', ['author_id' => $item->id]);
        });
    }

    /**
     * @param Builder $builder
     * @param string $sitemapName
     * @param callable $getUrlCallback
     *
     * @return void
     */
    private function fillSitemapWithRecords(Builder $builder, string $sitemapName, callable $getUrlCallback): void
    {
        $sitemap = Sitemap::create();

        /**
         * Split into files
         */
        $urlsPerFile = self::PAGES_PER_ONE_SITEMAP_FILE_LIMIT;
        $chunkSize = self::DB_QUERIES_CHUNK_SIZE;

        $pagesCount = 0;

        $totalCount = $builder->count();
        $fileIndex = 0;
        if ($totalCount > $urlsPerFile) {
            $sitemapFilePath = $this->getIndexedSitemapFilePath($sitemapName, $fileIndex);
            $sitemapFileName = $this->getIndexedSitemapFileName($sitemapName, $fileIndex);
        } else {
            $sitemapFilePath = $this->getSitemapFilePath($sitemapName);
            $sitemapFileName = $this->getSitemapFileName($sitemapName);
        }

        $builder
            /**
             * Chunking queries to limit memory usage
             */
            ->chunk($chunkSize, function (Collection $models) use (&$sitemap, &$pagesCount, $urlsPerFile,
                $getUrlCallback, $sitemapName, &$sitemapFilePath, &$sitemapFileName, &$fileIndex) {

                /**
                 * Add each page in sitemap file
                 */
                $models->each(function ($model) use (&$sitemap, &$pagesCount, $urlsPerFile, $getUrlCallback,
                    $sitemapName, &$sitemapFilePath, &$sitemapFileName, &$fileIndex) {

                    /**
                     * Each `$urlsPerFile` write new sitemap file
                     */
                    if($pagesCount >= $urlsPerFile) {
                        $sitemap->writeToFile($sitemapFilePath);
                        $this->addSitemapFile($sitemapFileName);

                        $sitemap = Sitemap::create();
                        $pagesCount = 0;
                        $sitemapFilePath = $this->getIndexedSitemapFilePath($sitemapName, ++$fileIndex);
                    }

                    $url = $getUrlCallback($model);

                    $sitemap->add(Url::create($url)
                        ->setLastModificationDate($model->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(1));

                    $pagesCount++;

                    return true;
                });

                return true;
            });

        if($pagesCount > 0) {
            $sitemap->writeToFile($sitemapFilePath);
            $this->addSitemapFile($sitemapFileName);
            unset($sitemap);
        }
    }

    /**
     * Статичные страницы
     *
     * @return void
     */
    private function generateStaticPagesSitemap(): void
    {
        $this->warn($this->getSitemapFileName(self::STATIC_PAGES_SITEMAP_NAME));

        $staticPages = Page::all()->filter(function ($page) {
            return (bool) $page->viewBag['is_hidden'] == false;
        })
        ->pluck('url')
        ->toArray();

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

        $sitemap = Sitemap::create();

        foreach ($this->sitemapFiles as $filePath) {
            $sitemap->add(Url::create($filePath)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(1));
        }

        $sitemap->add(Url::create($this->getSitemapFileName(self::STATIC_PAGES_SITEMAP_NAME))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.1));

        $sitemap->writeToFile($this->getSitemapFilePath('sitemap'));
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
     * @param string $name
     * @param int $index
     *
     * @return string
     */
    private function getIndexedSitemapFileName(string $name, int $index): string
    {
        return sprintf('%s_%d', $name, $index);
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
     * @param int $index
     *
     * @return string
     */
    private function getIndexedSitemapFilePath(string $name, int $index): string
    {
        return $this->getSitemapFilePath($this->getIndexedSitemapFileName($name, $index));
    }

    /**
     * @param string $sitemapFilePath
     *
     * @return void
     */
    private function addSitemapFile(string $sitemapFilePath): void
    {
        $this->sitemapFiles[] = $sitemapFilePath;
    }
}
