<?php namespace Books\Book\Components;

use Books\Book\Models\Book;
use Cms\Classes\ComponentBase;
use October\Rain\Database\Builder;

/**
 * AdvertBanner Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class AdvertBanner extends ComponentBase
{
    protected ?Book $book = null;

    public function componentDetails()
    {
        return [
            'name' => 'AdvertBanner Component',
            'description' => 'No description provided yet...'
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

        $this->book = Book::query()
            ->public()
            ->whereHas('advert', fn($advert) => $advert
                ->enabled()
                ->allowed()
                ->has('banner'))
            ->diffWithUnloved()
            ->inRandomOrder()
            ->first();
    }

    public function onRender()
    {
        foreach ($this->vals() as $key => $val) {
            $this->page[$key] = $val;
        }
    }


    public function vals()
    {
        return [
            'advert' => $this->book?->advert
        ];
    }

    public function onVisit()
    {
        return Book::find(post('book_id'))?->advert?->registerVisit();
    }
}
