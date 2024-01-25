<?php

namespace Books\Book\Traits;

trait FormatNumberTrait
{
    /**
     * @param mixed $number
     *
     * @return string
     */
    private function formatNumber(mixed $number): string
    {
        if (null === $number) {
            return '';
        }

        return number_format(floatval($number), 2, '.', ' ');
    }
}
