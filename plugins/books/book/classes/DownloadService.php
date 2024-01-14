<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\ElectronicFormats;
use Books\Book\Models\Book;
use Books\Book\Models\Edition;
use System\Models\File;

class DownloadService
{
    protected Edition $edition;

    public function __construct(public Book $book, public ElectronicFormats $format = ElectronicFormats::FB2)
    {
        $this->edition = $this->book->ebook;
    }

    public function getFile(): File
    {
        $file = $this->getElectronicFile();
        $this->edition->downloads()->firstOrCreate()->increment('count');

        return $file;
    }

    public function getElectronicFile()
    {
        if ($this->format !== ElectronicFormats::PDF) {
            $this->edition->{$this->format->value}?->delete();
        }

        return $this->generateElectronicFile();
    }

    public function generateElectronicFile(): File
    {
        if ($this->format === ElectronicFormats::PDF && $this->edition->pdf) {
            return $this->edition->{$this->format->value};
        }
        $this->edition->{$this->format->value}()->add($this->format->converter($this->book)->make());

        return $this->edition->{$this->format->value};
    }
}
