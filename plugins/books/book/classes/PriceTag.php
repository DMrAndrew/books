<?php
declare(strict_types=1);

namespace Books\Book\Classes;

use Books\AuthorPrograms\Models\AuthorsPrograms;
use Books\Book\Models\Discount;
use Books\Book\Models\Edition;
use Books\Book\Models\Promocode;
use Carbon\Carbon;
use October\Rain\Support\Facades\Str;
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
        $authorBirthday = $author?->birthday->format('d-m') ?? null;
        $nowDate = Carbon::now();

        $readerBirthdayProgram = AuthorsPrograms::userProgramReaderBirthday()->where('user_id', $author->id)->first();
        $newReaderProgram = AuthorsPrograms::userProgramNewReader()->where('user_id', $author->id)->first();
        $regularReaderProgram = AuthorsPrograms::userProgramRegularReader()->where('user_id', $author->id)->first();

        dd($readerBirthdayProgram, $newReaderProgram, $regularReaderProgram);

        if ($reader = Auth::getUser()) {
            $readerBirthday = $reader?->birthday->format('d-m') ?? null;
        }

        if ($authorBirthday === $nowDate->format('d-m')) {

        }
//        dd($reader);


    }
}
