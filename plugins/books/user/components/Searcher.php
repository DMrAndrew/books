<?php namespace Books\User\Components;

use Cache;
use Flash;
use Request;
use Redirect;
use Carbon\Carbon;
use ValidationException;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use Books\User\Classes\SearchManager;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Searcher Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Searcher extends ComponentBase
{
    protected $query;
    protected string $entity = 'books';
    protected bool $useCache = true;

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'Searcher Component',
            'description' => 'No description provided yet...'
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
        return hash('xxh3', strtolower($this->query . $this->entity));
    }

    protected function isRedirectRequire(): bool
    {
        return !starts_with($this->page->url, '/search');
    }

    protected function find(): array
    {
        $attempts = Auth::getUser() ? 20 : 10;
        if (!RateLimiter::attempt('search' . request()->ip(), $attempts, fn() => 1, 20)) {
            $ex = new ValidationException(['rateLimiter' => 'Превышен лимит запросов. Подождите 20 сек.']);
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
        if (!$this->query || strlen($this->query) < 2) {
            return [];
        }

        $results = (new SearchManager($this->query, $this->entity))->apply();
        $entity = $results->first(fn($i) => $i['name'] === $this->entity) ?? $results->first() ?? null;
        $this->page['search_query'] = $this->query;
        if($name = $entity['name']??false){
            $this->entity = $name;
        }
        return [
            'query' => $this->query,
            'entity' => $entity,
            'found' => $results,
        ];
    }

    protected function cached()
    {
        if ($this->useCache) {
            $sHash = $this->hash();
            if (Cache::has($sHash)) {
                return Cache::get($sHash);
            }
        }

        $partial = $this->renderPartial('@default', ['results' => $this->find()]);
        if ($this->useCache) {
            Cache::put($this->hash(), $partial, Carbon::now()->addMinutes(2));
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
        if ($e = post('entity')) {
            $this->entity = $e;
        }

        return [
            '#search_result' => $this->cached()
        ];
    }
}
