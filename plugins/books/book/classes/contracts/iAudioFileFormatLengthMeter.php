<?php
declare(strict_types=1);

namespace Books\Book\Classes\Contracts;

interface iAudioFileFormatLengthMeter
{
    public function __construct(string $filename);

    public function getDuration(): int;
}