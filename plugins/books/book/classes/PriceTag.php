<?php
declare(strict_types=1);

namespace Books\Book\Classes;

use Books\AuthorPrograms\Classes\Enums\ProgramsEnums;
use Books\Book\Models\Discount;
use Books\Book\Models\Edition;
use Books\Book\Models\Promocode;
use Carbon\Carbon;
use Exception;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

class PriceTag
{
    protected array $discountsArr = [];

    public function __construct(
        protected Edition    $edition,
        protected ?Discount $discount = null,
        protected ?Promocode $promocode = null,
        protected ?User $reader = null,
    ) {
        $this->discount ??= $this->edition->discounts()->active()->first();
        $this->promocode = $this->promocode ? $this->edition
            ->promocodes()
            ->code($this->promocode->code)
            ->alive()
            ->first() : null;
        $this->reader ??= Auth::getUser();
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

        if ($this->reader) {
            $this->checkLoadedRelation();

            $author = $this->edition->book->profile->user;
            $authorPrograms = $author->programs;

            $birthdayProgram = $authorPrograms->where('program', ProgramsEnums::READER_BIRTHDAY->value)->first();
            $newReaderProgram = $authorPrograms->where('program', ProgramsEnums::NEW_READER->value)->first();
            $regularReaderProgram = $authorPrograms->where('program', ProgramsEnums::REGULAR_READER->value)->first();

            if ($birthdayProgram) {
                if (
                    (Carbon::now() === $this->reader?->birthday?->subDay())
                    xor (Carbon::now() === $this->reader?->birthday?->addDay())
                    xor $this->reader?->birthday?->isBirthday()
                ) {
                    if (array_intersect($this->edition->book->bookGenre->pluck('genre_id')->toArray(), $this->reader->loved_genres)) {
                        $this->discountsArr['values'][] = $birthdayProgram->condition->percent;
                        if ($birthdayProgram->condition->percent >= $this->discountAmount()) {
                            $this->discountsArr['discount'] = [
                                'color' => 'green',
                                'text' => "{$birthdayProgram->condition->percent}% по программе \"День рождения читателя\""
                            ];
                        }
                    }
                }
            }

            $authorBooks = $this->edition->book->authors;
            $readerBooksPurchased = $this->reader
                ->ownedBooks
                ->whereIn('ownable_id', $authorBooks->pluck('book_id')->toArray());

            if ($regularReaderProgram && $readerBooksPurchased->count() > $regularReaderProgram->condition->books) {
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
                    $readerBooksPurchased->first()?->created_at,
                    $readerBooksPurchased->first()?->created_at->addDays($newReaderProgram->condition->days)
                )
                && $readerBooksPurchased->count() > 1
            ) {
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

    /**
     * @throws Exception
     */
    private function checkLoadedRelation()
    {
        if (! $this->edition->relationLoaded('book')) {
            throw new Exception('Relation `book` eager loading detected for $this->edition');
        }

        if (! $this->edition->book->relationLoaded('profile')) {
            throw new Exception('Relation `profile` eager loading detected for $this->edition->book');
        }

        if (! $this->edition->book->profile->relationLoaded('user')) {
            throw new Exception('Relation `user` eager loading detected for $this->edition->book->profile');
        }

        if (! $this->edition->book->profile->user->relationLoaded('programs')) {
            throw new Exception('Relation `programs` eager loading detected for $this->edition->book->profile->user');
        }

        if (! $this->edition->book->relationLoaded('authors')) {
            throw new Exception('Relation `authors` eager loading detected for $this->edition->book');
        }
    }
}
