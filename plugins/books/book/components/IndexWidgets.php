<?php namespace Books\Book\Components;

use Books\Book\Classes\Enums\WidgetEnum;
use Books\Catalog\Classes\RecommendsService;
use Books\User\Classes\CookieEnum;
use Cms\Classes\ComponentBase;
use Cookie;
use Flash;
use Illuminate\Support\Facades\RateLimiter;
use Lang;
use October\Rain\Database\Collection;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * IndexWidgets Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class IndexWidgets extends ComponentBase
{

    protected ?User $user;
    private Collection|null $genres = null;
    private Collection|null $loved_genres = null;
    private Collection|null $unloved_genres = null;

    const RATE_LIMIT_ATTEMPTS = 20;
    const RATE_DELAY_SECONDS = 60;

    public function componentDetails()
    {
        return [
            'name' => 'Виджеты на главной странице',
            'description' => 'Виджеты + чекбоксы выбора для неавторизованного пользователя'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub

        $this->user = Auth::getUser();
        if (request()->header('X-October-Request-Handler') !== 'IndexWidgets::onRefreshWidgets') {
            $this->setProps();
        }
       if($this->user){
           $this->bindWidgets();
       }

    }

    public function setProps()
    {
        if (!$this->user) {
            if (!Cookie::has(CookieEnum::LOVED_GENRES->value)) {
                $this->loved_genres = $this->genres = RecommendsService::defaultGenresBuilder()->get();
                $this->unloved_genres = new Collection();
                $this->setCookie();
            } else {
                $this->loved_genres = RecommendsService::defaultGenresBuilder()->whereIn('id', getLovedFromCookie())->get();
                $this->unloved_genres = RecommendsService::defaultGenresBuilder()->whereIn('id', getUnlovedFromCookie())->get();
                $this->genres = RecommendsService::defaultGenresBuilder()->get();
            }
        }
    }

    public function onRender()
    {
        parent::onRender(); // TODO: Change the autogenerated stub
        foreach ($this->vals() as $key => $val) {
            $this->page[$key] = $val;
        }

    }

    public function vals(): array
    {
        $this->markSelected($this->genres, $this->loved_genres);
        return [
            'favorite_genres_list' => $this->genres
        ];

    }

    public function markSelected(Collection|null &$source, Collection|null $array, string $flag = 'selected'): void
    {
        if ($source && $array) {
            $array_ids = $array->pluck('id');
            foreach ($source as $item) {
                $item[$flag] = (bool)($array_ids->intersect([$item->id]))->count();
            }
        }
    }

    public function onToggleFavorite(): array
    {
        $key = __FUNCTION__ . request()->ip();
        if (!RateLimiter::attempt($key, self::RATE_LIMIT_ATTEMPTS, fn() => 1, self::RATE_DELAY_SECONDS)) {
            Flash::error(Lang::get('books.catalog::lang.plugin.too_many_attempt') . ' Попробуйте снова через ' . RateLimiter::availableIn($key) . ' сек.');
            return [];
        }
        if ($this->user) {
            return [];
        }
        $genre = RecommendsService::defaultGenresBuilder()->where('id', (int)post('id'))->get();
        if (!$genre->count()) {
            return [];
        }
        $toggle = fn($array, $value) => $array->pluck('id')->intersect($value->pluck('id'))->count() ? $array->diff($value) : $array->merge($value);

        foreach (['loved_genres', 'unloved_genres'] as $list) {
            $this->{$list} = $toggle($this->{$list}, $genre);
        }
        $this->setCookie();

        return $this->render();
    }


    public function render(): array
    {
        return [
            '#checkboxs-container' => $this->renderPartial('site/favorite', $this->vals())
        ];
    }

    public function onRefreshWidgets(): array
    {
        if(!$this->user){
            $this->bindWidgets();
        }
        return [
            '#widgets-container' => $this->renderPartial('site/thematic')
        ];
    }

    public function setCookie(): void
    {
        if ($this->loved_genres) {
            CookieEnum::LOVED_GENRES->set($this->loved_genres->pluck('id')->toArray());
        }

        if ($this->unloved_genres) {
            CookieEnum::UNLOVED_GENRES->set($this->unloved_genres->pluck('id')->toArray());
        }

    }

    public function bindWidgets(): void
    {
        $new = $this->addComponent(Widget::class, 'widget_new');
        $new->setUpWidget(WidgetEnum::new, withAll: true, short: true);

        $interested = $this->addComponent(Widget::class, 'interested');
        $interested->setUpWidget(WidgetEnum::interested, ...['short' => true]);

        $gainingPopularity = $this->addComponent(Widget::class, 'gainingPopularity');
        $gainingPopularity->setUpWidget(WidgetEnum::gainingPopularity, withAll: true);

        $hot_new = $this->addComponent(Widget::class, 'hotNew');
        $hot_new->setUpWidget(WidgetEnum::hotNew, withAll: true);

        $recommend = $this->addComponent(Widget::class, 'recommend');
        $recommend->setUpWidget(WidgetEnum::recommend, short: true, withAll: true);

        $todayDiscount = $this->addComponent(Widget::class, 'todayDiscount');
        $todayDiscount->setUpWidget(WidgetEnum::todayDiscount, withAll: true);

        $bestsellers = $this->addComponent(Widget::class, 'bestsellers');
        $bestsellers->setUpWidget(WidgetEnum::bestsellers, withAll: true);

        $top = $this->addComponent(Widget::class, 'top');
        $top->setUpWidget(WidgetEnum::top, withAll: true);
    }

}
