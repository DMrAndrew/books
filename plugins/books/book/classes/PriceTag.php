<?php
declare(strict_types=1);

namespace Books\Book\Classes;

use Books\AuthorPrograms\Models\AuthorsPrograms;
use Books\Book\Models\Author;
use Books\Book\Models\Discount;
use Books\Book\Models\Edition;
use Books\Book\Models\Promocode;
use Books\Book\Models\UserBook;
use Carbon\Carbon;
use RainLab\User\Facades\Auth;

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
        $this->fillDiscountArray();
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
        return $this->discountsArr ? max($this->discountsArr) : 0;
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

    public function fillDiscountArray()
    {
        if ($discount = $this->discount?->amount) {
            $this->discountsArr[] = $discount;
        }

        if ($reader = Auth::getUser()) {
            $profile = $this->edition->book->profile;
            $authorAccount = $profile->user;

            $readerBirthdayProgram = AuthorsPrograms::userProgramReaderBirthday()->where('user_id', $authorAccount->id)->first();
            $newReaderProgram = AuthorsPrograms::userProgramNewReader()->where('user_id', $authorAccount->id)->first();
            $regularReaderProgram = AuthorsPrograms::userProgramRegularReader()->where('user_id', $authorAccount->id)->first();

            if ($reader?->birthday->isBirthday() and $readerBirthdayProgram) {
                $this->discountsArr[] = $readerBirthdayProgram->condition->percent;
            }

            $authorBooks = Author::where('profile_id', $profile->id)->get()->pluck('book_id');
            $readerBooksPurchased = UserBook::where('user_id', $reader->getAuthIdentifier())
                ->whereIn('ownable_id', $authorBooks)
                ->orderBy('created_at', 'ASC');

            if ($regularReaderProgram && $readerBooksPurchased->count() >= $regularReaderProgram->condition->books) {
                $this->discountsArr[] = $regularReaderProgram->condition->percent;
            }

            if ($newReaderProgram
                && Carbon::now()->between(
                    $readerBooksPurchased->first()?->created_at,
                    $readerBooksPurchased->first()?->created_at->addDays($newReaderProgram->condition->days)
                )) {
                $this->discountsArr[] = $newReaderProgram->condition->percent;
            }
        }

    }

    public function discountExists()
    {
        return (boolean)$this->discountsArr;
    }
}
