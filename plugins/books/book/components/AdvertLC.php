<?php namespace Books\Book\Components;

use Books\Book\Models\Advert;
use Books\Book\Models\Book;
use Books\FileUploader\Components\ImageUploader;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Redirect;

/**
 * AdvertLC Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class AdvertLC extends ComponentBase
{
    protected ?Book $book;
    protected User $user;

    public function componentDetails()
    {
        return [
            'name' => 'AdvertLC Component',
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
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $this->user = Auth::getUser();
        $this->setBook();
        $this->setUpImageUploder();


    }

    public function onRun()
    {
        $this->setUpImageUploder();
    }


    public function setUpImageUploder()
    {
        if ($this->book) {
            $banner = $this->addComponent(ImageUploader::class, 'advertUploader', [
                'modelClass' => Advert::class,
                'modelKeyColumn' => 'banner',
                'deferredBinding' => false,
                'imageWidth' => 552,
                'imageHeight' => 224,
            ]);
            $banner->bindModel('banner', $this->book->advert);
        }
    }

    public function onRefreshFiles()
    {
        $this->pageCycle();
    }

    public function setBook(): void
    {
        $this->book = $this->query()
            ->with(['advert', 'advert.visits', 'ebook'])
            ->find(
                post('value') // Из селекта книги
                ?? post('book_id') // из кнопки "Рекламировать"
                ?? $this->param('book_id')); // из параметра url

    }

    public function onRender()
    {
        foreach ($this->vals() as $key => $val) {
            $this->page[$key] = $val;
        }
    }

    public function query(){
        return $this->user->toBookUser()
            ->booksInAuthorOrder()
            ->public()->defaultEager();
    }

    public function vals(): array
    {

        $visit_table = $this->book?->advert->visits->groupBy(fn($i) => $i->created_at->format('d.m.y'))->map->count();
        return [
            'book' => $this->book,
            'books' => $this->query()->get(),
            'sold_count' => $this->book?->ebook->getSoldCountAttribute(),
            'days_on_sale' => $this->book?->ebook->sells()->orderBy('created_at')->first()?->created_at
                ->diffInDays(Carbon::now()) ?? '-',
            'visited_by_advert' => $this->book?->advert->visits->count(),
            'visits_table' => $visit_table,
            'visits_total' => $visit_table?->sum()
        ];
    }

    public function render()
    {
        $this->pageCycle();
        return [
            '#advert_spawn' => $this->renderPartial('@default', $this->vals())
        ];
    }

    public function onToggleState()
    {
        $this->book?->advert->toggleState()->save();
        return $this->render();
    }

    public function onChangeBook()
    {
        if ($this->book) {
            return Redirect::to('/lc-advert/' . $this->book->id);
        }
        return $this->render();
    }
}
