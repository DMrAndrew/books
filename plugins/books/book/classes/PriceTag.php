<?php
declare(strict_types=1);

namespace Books\Book\Classes;

use Books\Book\Models\Discount;
use Books\Book\Models\Edition;
use Books\Book\Models\Promocode;
use Carbon\Carbon;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

class PriceTag
{
    protected array $discountsArr = [];

    public function __construct(protected Edition    $edition,
                                protected ?Discount  $discount = null,
                                protected ?Promocode $promocode = null
    )
    {
        $this->discount ??= $this->edition->discounts()->active()->first();
        $this->promocode = $this->promocode ? $this->edition
            ->promocodes()
            ->code($this->promocode->code)
            ->alive()
            ->first() : null;
    }

    public function price(): int
    {
        return (int)floor($this->initialPrice() - ($this->initialPrice() * $this->discountAmount() / 100));
    }

    public function initialPrice(): int
    {
        return $this->edition->price ?? 0;
    }

    public function odds(): int
    {
        return $this->price() - $this->initialPrice();
    }

    public function discountAmount(): int
    {
        if ($this->promocode) {
            return 100;
        }
        $this->authorProgramDiscount();
        return $this->discount?->amount ?? 0;
    }

    /**
     * @return Promocode|null
     */
    public function getPromocode(): ?Promocode
    {
        return $this->promocode;
    }

    /**
     * @return Discount|null
     */
    public function getDiscount(): ?Discount
    {
        return $this->discount;
    }

    public function authorProgramDiscount()
    {
        $author = $this->edition->book->profile->user;
        $authorBirthday = $author->birthday->format('d-m');
        $reader = Auth::getUser();
        $readerBithday = $reader->birthday->format('d-m');
        $nowDate = Carbon::now();
        $programs = $this->edition->book->profile->user->programs();
//        dd($reader->ownedBooks->count());


    }
}
