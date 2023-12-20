<?php

namespace Books\Book\Classes\Converters;

use Books\Book\Classes\Enums\ElectronicFormats;
use Books\Book\Models\Book;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use System\Models\File;

class BaseConverter
{
    public File $file;

    public ?User $user;

    public ?bool $isFullAccess = null;

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

    public function user()
    {
        $this->user ??= Auth::getUser();

        return $this->user;
    }

    public function generate(): string
    {
        return '';
    }

    public function filename(): string
    {
        return $this->book->title.'.'.$this->format->value;
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

    public function isFullAccess(): bool
    {
        $this->isFullAccess ??= $this->book->ebook->isFree() || ($this->user() && ($this->book->ebook->isSold($this->user()) || $this->book->isAuthor($this->user()->profile)));

        return $this->isFullAccess;
    }

    public function chapters()
    {
        return $this->book->ebook->chapters
            ->when(! $this->isFullAccess(), fn ($collection) => $collection->filter->isFree());
    }

    public function mark(): string
    {
        return sprintf('<i>Данный %s загружен на портале %s.</i>', $this->isFullAccess() ? 'текст' : 'ознакомительный фрагмент', $this->makeDomainLink());
    }

    public function endMark(): string
    {
        return $this->isFullAccess() ? '' : sprintf('<i>Конец ознакомительного фрагмента. Полный текст Вы можете приобрести на портале %s</i>', $this->makeBookLink());
    }

    public function makeDomainLink(): string
    {
        return sprintf('<a target="_blank" href="%s">Время книг</a>', request()->url());
    }

    public function makeBookLink(): string
    {
        return sprintf('<a target="_blank" href="%s">Время книг</a>', sprintf('%s/book-card/%s', request()->url(), $this->book->id));
    }
}
