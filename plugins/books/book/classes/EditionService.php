<?php

namespace Books\Book\Classes;

use Books\Book\Models\Edition;

class EditionService
{
    public function __construct(protected Edition $edition)
    {
    }
}
