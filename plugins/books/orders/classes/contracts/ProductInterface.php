<?php

namespace Books\Orders\Classes\Contracts;

use Books\Book\Classes\PriceTag;
use October\Rain\Database\Relations\MorphMany;

interface ProductInterface
{
    public function priceTag(): PriceTag;

    public function products(): MorphMany;
}
