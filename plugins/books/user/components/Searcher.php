<?php

namespace Books\User\Components;

use Books\User\Classes\SearchManager;
use Cache;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;
use Log;
use RainLab\User\Facades\Auth;
use Redirect;
use Request;
use ValidationException;

/**
 * Searcher Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Searcher extends ComponentBase
{
    protected $query;

    protected bool $useCache;
    protected Carbon $cacheTTL;

    protected int $delay_seconds = 20;

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'Searcher Component',
            'description' => 'No description provided yet...',
        ];
    }

    /**
     * defineProperties for the component
     *
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        $this->query = trim($this->param('query'));
        $this->page['search_query'] = $this->query;

        $this->useCache = config('cache.cache_search_results');
        $this->cacheTTL = Carbon::now()->addMinutes(2);
    }

    public function onRender()
    {
        if (!$this->isRedirectRequire()) {
            $this->page['results'] = $this->find();
        }
    }

    protected function hash(): string
    {
        return hash('xxh3', strtolower($this->query));
    }

    protected function isRedirectRequire(): bool
    {
        return !starts_with($this->page->url, '/search');
    }

    /**
     * @throws ValidationException
     */
    protected function find()
    {
        $attempts = Auth::getUser() ? 20 : 10;
        if (!RateLimiter::attempt('search' . request()->ip(), $attempts, fn() => 1, $this->delay_seconds)) {
            throw new ValidationException(['rateLimiter' => 'Превышен лимит запросов. Подождите 20 сек.']);
        }

        return (new SearchManager())->apply($this->query);
    }

    /**
     * @throws ValidationException
     */
    protected function cached()
    {
        if ($this->useCache && Cache::has($this->hash())) {
            return Cache::get($this->hash());
        }

        $partial = $this->renderPartial('@default', ['results' => $this->find()]);
        if ($this->useCache) {
            Cache::put($this->hash(), $partial, $this->cacheTTL);
        }

        return $partial;
    }

    public function onSearch(): array|RedirectResponse
    {
        try {
            $q = trim(post('query'));
            if ($this->isRedirectRequire()) {
                return Redirect::to('/search/' . $q);
            }
            $this->query = $q;
            $this->page['search_query'] = $this->query;

            return [
                '#search_result' => $this->cached(),
            ];
        } catch (Exception $exception) {
            Flash::error($exception->getMessage());
            Log::error($exception->getMessage());
            return [];
        }
    }
}
