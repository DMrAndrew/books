<?php declare(strict_types=1);

namespace Books\Moderation\ServiceProviders;

use Books\Moderation\Classes\PremoderationDrafts;
use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Support\ServiceProvider;

class DraftsServiceProvider extends ServiceProvider
{
    public function register()
    {
        if (method_exists($this->app['db']->connection()->getSchemaBuilder(), 'useNativeSchemaOperationsIfPossible')) {
            Schema::useNativeSchemaOperationsIfPossible();
        }

        $this->app->singleton(PremoderationDrafts::class, function () {
            return new PremoderationDrafts();
        });

        Blueprint::macro('drafts', function (
            string $uuid = null,
            string $publishedAt = null,
            string $isPublished = null,
            string $isCurrent = null,
            string $publisherMorphName = null,
        ) {
            /** @var Blueprint $this */
            $uuid ??= config('books.moderation::column_names.uuid', 'uuid');
            $publishedAt ??= config('books.moderation::column_names.published_at', 'published_at');
            $isPublished ??= config('books.moderation::column_names.is_published', 'is_published');
            $isCurrent ??= config('books.moderation::column_names.is_current', 'is_current');
            $publisherMorphName ??= config('books.moderation::column_names.publisher_morph_name', 'publisher_morph_name');

            $this->uuid($uuid)->nullable();
            $this->timestamp($publishedAt)->nullable();
            $this->boolean($isPublished)->default(false);
            $this->boolean($isCurrent)->default(false);

            $morphsShortIndexName = $this->getTable() . '_pt_pi';
            $this->nullableMorphs($publisherMorphName, $morphsShortIndexName);

            // short index names
            $indexName = $this->getTable() . '_u_ip_ic';
            $this->index([$uuid, $isPublished, $isCurrent], $indexName);
        });

        Blueprint::macro('dropDrafts', function (
            string $uuid = null,
            string $publishedAt = null,
            string $isPublished = null,
            string $isCurrent = null,
            string $publisherMorphName = null,
        ) {
            /** @var Blueprint $this */
            $uuid ??= config('books.moderation::column_names.uuid', 'uuid');
            $publishedAt ??= config('books.moderation::column_names.published_at', 'published_at');
            $isPublished ??= config('books.moderation::column_names.is_published', 'is_published');
            $isCurrent ??= config('books.moderation::column_names.is_current', 'is_current');
            $publisherMorphName ??= config('books.moderation::column_names.publisher_morph_name', 'publisher_morph_name');

            // short index names
            $indexName = $this->getTable() . '_u_ip_ic';
            $this->dropIndex($indexName);

            $morphsShortIndexName = $this->getTable() . '_pt_pi';
            $this->dropMorphs($publisherMorphName, $morphsShortIndexName);

            $this->dropColumn([
                $uuid,
                $publishedAt,
                $isPublished,
                $isCurrent,
            ]);
        });
    }
    
//    protected function registerRoutes(): void
//    {
//        Route::macro('withDrafts', function (\Closure $routes): void {
//            Route::middleware(WithDraftsMiddleware::class)->group($routes);
//        });
//    }
}
