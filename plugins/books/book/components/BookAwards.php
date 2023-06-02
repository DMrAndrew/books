<?php namespace Books\Book\Components;

use Books\Book\Models\Award;
use Books\Book\Models\Book;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Log;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * BookAwards Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class BookAwards extends ComponentBase
{
    protected ?Book $book;
    protected ?User $user;

    public function componentDetails()
    {
        return [
            'name' => 'BookAwards Component',
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
        $this->book = Book::query()->public()->find($this->param('book_id'));
        $this->user = Auth::getUser();
    }

    public function onRender(): void
    {
        foreach ($this->vals() as $key => $val) {
            $this->page[$key] = $val;
        }
    }

    public function vals()
    {
        return [
            'stockAward' => Award::all(),
            'awards' => (Book::query()->public()
                ->with(['awards' => fn($awards) => $awards->with(['profile', 'award'])->orderBy('created_at', 'desc')])
                ->find($this->book?->id)?->awards ?? collect())
                ->groupBy(fn($i) => $i->award->type->value)->sortBy(fn($i) => $i->first()->award->type->value),
            'user' => $this->user,
            'buyAwardsIsAllowed' => $this->buyAwardsIsAllowed()
        ];
    }

    public function onOpenForm(): array
    {
        return [
            '#awards_form' => $this->renderPartial('@awards_form', [
                'stockAward' => Award::all(),
            ]),
        ];
    }

    public function buyAwardsIsAllowed(): bool
    {
        return $this->book?->exists ?? false;
    }

    public function onChangeAwardBag(): array
    {
        $awards = Award::query()->whereIn('id', $this->getAwardsIds())->sum('price');

        return ['#awardPriceSpawn' => $awards . ' ₽'];
    }

    public function getAwardsIds(): array
    {
        return collect(post('awards'))->filter(fn($i) => !!$i)->keys()->toArray();
    }

    public function render()
    {
        return [
            '#awards_spawn' => $this->renderPartial('@default', $this->vals())
        ];
    }
}
