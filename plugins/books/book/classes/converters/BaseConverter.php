<?php

namespace Books\Book\Classes\Converters;

use Books\Book\Classes\Enums\ElectronicFormats;
use Books\Book\Models\Book;
use RainLab\User\Facades\Auth;
use System\Models\File;

class BaseConverter
{
    public File $file;

    public ElectronicFormats $format = ElectronicFormats::FB2;

    public function __construct(public Book $book)
    {
        $this->file = new File();
        $this->book->load(['cover', 'genres', 'tags', 'profile']);
        $this->book->ebook->load(['chapters.content']);
    }

    public function make(): File
    {
        $this->file->fromData($this->generate(), $this->filename());

        return $this->file;
    }

    public function generate(): string
    {
        return '';
    }

    public function filename(): string
    {
        return sprintf('%s.%s', $this->book->title, $this->format->value);
    }

    public function save(): void
    {
        file_put_contents($this->filename(), $this->generate());
    }

    public function has_cover(): bool
    {
        return $this->book->cover->exists;
    }

    public function printDate()
    {
        return $this->book->ebook->published_at ?? $this->book->ebook->created_at;
    }

    public function chapters()
    {
        $user = Auth::getUser();

        if ($this->book->ebook->isFree() || ($user && ($this->book->ebook->isSold($user) || $this->book->isAuthor($user->profile)))) {
            return $this->book->ebook->chapters;
        }

        return $this->book->ebook->chapters->filter->isFree();
    }
}
