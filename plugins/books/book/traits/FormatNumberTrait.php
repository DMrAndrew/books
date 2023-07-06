<?php

namespace Books\Book\Traits;

trait FormatNumberTrait
{
    /**
     * @param $number
     *
     * @return string
     */
    private function formatNumber($number): string
    {
        return number_format($number, 2, '.', ' ');
    }
}
