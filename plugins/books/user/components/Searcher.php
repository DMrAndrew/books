<?php

namespace Books\User\Components;

use Books\User\Classes\SearchManager;
use Cache;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Flash;
use Illuminate\Support\Facades\RateLimiter;
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

    protected bool $useCache = false;

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

    protected function find()
    {
        $attempts = Auth::getUser() ? 20 : 10;
        if (!RateLimiter::attempt('search' . request()->ip(), $attempts, fn() => 1, 20)) {
            $ex = new ValidationException(['rateLimiter' => 'Превышен лимит запросов. Подождите 20 сек.']);
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }

        return (new SearchManager())->apply($this->query);
    }

    protected function cached()
    {
        if ($this->useCache) {
            if (Cache::has($this->hash())) {
                return Cache::get($this->hash());
            }
        }

        $partial = $this->renderPartial('@default', ['results' => $this->find()]);
        if ($this->useCache) {
            Cache::put($this->hash(), $partial, Carbon::now()->addMinute());
        }

        return $partial;
    }

    public function onSearch()
    {
        $q = trim(post('query'));
        if ($this->isRedirectRequire()) {
            return Redirect::to('/search/' . $q);
        }
        $this->query = $q;
        $this->page['search_query'] = $this->query;

        return [
            '#search_result' => $this->cached(),
        ];
    }
}
