<?php
declare(strict_types=1);

namespace Books\Book\Classes;

use Books\Book\Models\Discount;
use Books\Book\Models\Edition;
use Books\Book\Models\Promocode;

class PriceTag
{
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


}
