<?php namespace Books\Book\Components;

use Books\Book\Classes\PriceTag;
use Books\Book\Models\Book;
use Books\Book\Models\Discount;
use Books\Book\Models\Edition;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Event;
use Flash;
use Illuminate\Database\Eloquent\Collection;
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

    protected ?Edition $edition;

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

        $this->edition = $this->getEdition();
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
        $discounts = $this->getDiscounts();

        return [
            'editions' => $this->getNotFreeAccountEditions(),
            'active_at' => Carbon::tomorrow()->format('d.m.Y'),
            'editionItem' => $this->getEdition(),
            'discounts' => $discounts->flatten(1)->sortByDesc('active_at'),
        ];
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
                'priceTag' => $discount ? new PriceTag($this->edition, $discount) : null
            ]))
        ];
    }

    public function onCreateDiscount()
    {
        if (!$this->edition) {
            Flash::error("Издание не найдено");

            return [];
        }

        $discount = $this->makeDiscount();
        if ($this->edition->discounts()->alreadySetInMonth($discount->active_at)->exists()) {
            Flash::error('За календарный месяц можно установить только одну скидку для издания. Попробуйте другой месяц.');
            return [];
        }
        $this->edition->discounts()->add($discount);

        if ($discount->active_at->eq(today()->startOfDay())) {
            Event::fire('books.book::edition.discounted', [$discount]);
        }

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
        if ($this->edition) {
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

    /**
     * @return Collection
     */
    private function getNotFreeAccountEditions(): Collection
    {
        $books = $this->user->toBookUser()->booksInAuthorOrder()->get();

        return Edition::query()
            ->whereIn('book_id', $books->pluck('id')->toArray())
            ->free(false)
            ->get();
    }

    private function getEdition()
    {
        $editions = $this->getNotFreeAccountEditions();

        return $editions
            ->where('id', post('value') ?? post('edition_id') ?? $this->param('edition_id'))
            ->first();
    }

    private function getDiscounts()
    {
        $editions = $this->getNotFreeAccountEditions();

        return Discount::query()
            ->whereIn('edition_id', $editions->pluck('id')->toArray())
            ->get();
    }

}
