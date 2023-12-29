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
        return array_key_exists('values', $this->discountsArr) ? max($this->discountsArr['values']) : 0;
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
            $this->discountsArr['values'][] = $discount;
            if ($discount > $this->discountAmount()) {
                $this->discountsArr['discount'] = [
                    'color' => 'purple',
                    'text' => "{$discount}% на книгу"
                ];
            }
        }

        if ($reader = Auth::getUser()) {
            $authorProfile = $this->edition->book->profile;
            $authorAccount = $authorProfile->user;

            $readerBirthdayProgram = AuthorsPrograms::userProgramReaderBirthday()->where('user_id', $authorAccount->id)->first();
            $newReaderProgram = AuthorsPrograms::userProgramNewReader()->where('user_id', $authorAccount->id)->first();
            $regularReaderProgram = AuthorsPrograms::userProgramRegularReader()->where('user_id', $authorAccount->id)->first();


            if ($readerBirthdayProgram) {
                if (Carbon::now() === $reader?->birthday->subDay()
                    || $reader?->birthday->isBirthday()
                    || Carbon::now() === $reader?->birthday->addDay()
                ) {
                    if (array_intersect($this->edition->book->bookGenre->pluck('genre_id')->toArray(), $reader->loved_genres)) {
                        $this->discountsArr['values'][] = $readerBirthdayProgram->condition->percent;
                        if ($readerBirthdayProgram->condition->percent >= $this->discountAmount()) {
                            $this->discountsArr['discount'] = [
                                'color' => 'green',
                                'text' => "{$readerBirthdayProgram->condition->percent}% по программе \"День рождения читателя\""
                            ];
                        }
                    }
                }

            }

            $authorBooks = Author::where('profile_id', $authorProfile->id)->get()->pluck('book_id');
            $readerBooksPurchased = UserBook::where('user_id', $reader->getAuthIdentifier())
                ->whereIn('ownable_id', $authorBooks)
                ->orderBy('created_at', 'ASC');

            if ($regularReaderProgram && $readerBooksPurchased->count() >= $regularReaderProgram->condition->books) {
                $this->discountsArr['values'][] = $regularReaderProgram->condition->percent;
                if ($regularReaderProgram->condition->percent >= $this->discountAmount()) {
                $this->discountsArr['discount'] = [
                    'color' => 'orange',
                    'text' => "{$regularReaderProgram->condition->percent}% по программе \"Постоянный читатель\""
                ];
                }
            }

            if ($newReaderProgram
                && Carbon::now()->between(
                    $newReaderProgram->first()?->created_at,
                    $newReaderProgram->first()?->created_at->addDays($newReaderProgram->condition->days)
                )) {
                if ($newReaderProgram->condition->percent >= $this->discountAmount()) {
                    $this->discountsArr['values'][] = $newReaderProgram->condition->percent;
                    $this->discountsArr['discount'] = [
                        'color' => 'red',
                        'text' => "{$newReaderProgram->condition->percent}% по программе \"Новый читатель\""
                    ];
                }
            }
        }
    }

    public function discountExists()
    {
        return (boolean)$this->discountsArr ?? false;
    }

    public function getDiscountInfo()
    {
        return array_key_exists('discount', $this->discountsArr) ? $this->discountsArr['discount'] : [];
    }
}
