<?php namespace Books\Book\Components;

use Books\Book\Classes\PriceTag;
use Books\Book\Models\Book;
use Books\Book\Models\Discount;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Flash;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use ValidationException;
use Validator;

/**
 * DiscountLC Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class DiscountLC extends ComponentBase
{

    protected User $user;

    protected ?Book $book;

    public function componentDetails()
    {
        return [
            'name' => 'DiscountLC Component',
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

        $this->book = $this->getBook();
    }

    public function onRender()
    {
        foreach ($this->vals() as $key => $val) {
            $this->page[$key] = $val;
        }
        if ($this->param('book_id')) {
            $this->page['active'] = true;
        }
    }

    public function vals()
    {
        return [
            'books' => $this->query()->get(),
            'active_at' => Carbon::tomorrow()->format('d.m.Y'),
            'bookItem' => $this->getBook(),
            'discounts' => $this->user->profile
                ->books()
                ->has('ebook.discounts')
                ->with('ebook.discounts', 'ebook.discounts.edition', 'ebook.discounts.edition.book')
                ->get()->map->ebook->pluck('discounts')->flatten(1)->sortBy('active_at')
        ];
    }


    public function query()
    {
        return $this->user->profile->books()->allowedForDiscount();
    }


    public function getBook()
    {
        return $this->query()->find(post('value') ?? post('book_id') ?? $this->param('book_id'));
    }

    public function onChangeEdition(): array
    {
        return [
            '#create_discount_modal_content' => $this->renderPartial('@discount_modal_content', array_merge(post(), [
                'active' => true,
            ]))
        ];
    }

    public function onDelete()
    {
        $discount = Discount::query()->active()->find(post('id'));
        if ($discount) {
            Flash::error('Нельзя удалить активную скидку.');
            return [];
        }
        //TODO ref
        Discount::find(post('id'))->delete();
        return $this->render();
    }

    public function onSetDiscount()
    {
        $discount = $this->makeDiscount();

        return [
            '#create_discount_modal_content' => $this->renderPartial('@discount_modal_content', array_merge(post(), [
                'active' => true,
                'priceTag' => $discount ? new PriceTag($this->book->ebook, $discount) : null
            ]))
        ];
    }

    public function onCreateDiscount()
    {
        if (!$this->book) {
            Flash::error('Книга не найдена');
            return [];
        }

        $discount = $this->makeDiscount();
        if ($this->book->ebook->discounts()->alreadySetInMonth($discount->active_at)->exists()) {
            Flash::error('За календарный месяц можно установить только одну скидку для книги. Попробуйте другой месяц.');
            return [];
        }
        $this->book->ebook->discounts()->add($discount);
        return $this->render();
    }

    public function render()
    {
        return [
            '#discount_spawn' => $this->renderPartial('@default', $this->vals())
        ];
    }

    public function makeDiscount(): ?Discount
    {
        if ($this->book) {
            $discount = new Discount();
            $data = collect(post())->only(['active_at', 'amount'])->toArray();
            $discount->removeValidationRule('amount', 'max:100');
            $discount->addValidationRule('amount', 'max:40');

            $v = Validator::make($data, $discount->rules);
            if ($v->fails()) {
                Flash::error($v->messages()->first());
                throw new ValidationException($v);
            }

            $discount->fill($data);
            return $discount;
        }
        return null;
    }

}
