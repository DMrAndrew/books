<?php

namespace Books\Book\Classes\Converters;

use Books\Book\Classes\Enums\ElectronicFormats;
use Books\Book\Models\Book;
use Books\Book\Models\Edition;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use System\Models\File;

class BaseConverter
{
    public File $file;

    public ?User $user;

    public ?bool $isFullAccess = null;

    public ?bool $isSold = null;

    public ElectronicFormats $format = ElectronicFormats::FB2;

    public function __construct(public Book $book)
    {
        $this->file = new File();
        $this->book->load(['cover', 'genres', 'tags', 'profile', 'ebook', 'ebook.chapters.content', 'profiles']);
    }

    public function make(): File
    {

        switch ($this->format) {
            case ElectronicFormats::EPUB:

                $this->generate();
                break;
            default:

                $this->file->fromData($this->generate(), $this->filename());
                break;


        }

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
        return $this->title().'.'.$this->format->value;
    }

    public function title(): string
    {
        return str_replace(' ', '_', translit($this->book->title));
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
        $this->isFullAccess ??= $this->book->ebook->isFree() || ($this->user() && ($this->isSold() || $this->book->isAuthor($this->user()->profile)));

        return $this->isFullAccess;
    }

    public function chapters()
    {
        return $this->book->ebook->chapters
            ->when(! $this->isFullAccess(), fn ($collection) => $collection->filter->isFree());
    }

    public function annotation(string $annotation = ''): string
    {
        return sprintf(collect([
            '<p><i>%s</i></p>', // аннотация
            '<p><strong>Автор: </strong>%s</p>', // авторы
            '<p><strong>Жанр: </strong><i>%s</i></p>',
            '<p><strong>Теги: </strong><i>%s</i></p>',
            '<p>%s</p>', // подпись 1
            '<p>%s</p>', // номер заказа
            '<p>%s</p>', // Дата
            '<p>***</p>',
        ])->join(''),
            $annotation ?: $this->book->annotation,
            $this->book->profiles->pluck('username')->join(', '),
            $this->book->genres->pluck('name')->join(', ') ?: '-',
            $this->book->tags->pluck('name')->join(', ') ?: '-',
            $this->printDate()->format('d.m.Y'),
            $this->mark(),
            $this->order());
    }

    public function isSold(): bool
    {
        $this->isSold ??= $this->book->ebook->isSold($this->user());

        return $this->isSold;
    }

    public function order(): string
    {
        if (! $this->user() || ! $this->isSold()) {
            return '';
        }
        $order = $this->user()->orders()->whereHas('products', function ($query) {
            $query->whereHasMorph('orderable', [Edition::class], function ($q) {
                $q->where('id', $this->book->ebook->id);
            });
        })->first();

        if (! $order) {
            return '';
        }

        return sprintf('<i>Заказ №%s</i>', $order->id);
    }

    public function mark(): string
    {
        return sprintf('<i>Данный %s загружен на портале %s.</i>', $this->isFullAccess() ? 'текст' : 'ознакомительный фрагмент', $this->makeLink());
    }

    public function endMark(): string
    {
        return $this->isFullAccess() ? '' : sprintf('<p>***</p><p><i>Конец ознакомительного фрагмента. Полный текст Вы можете приобрести на портале %s</i></p>', $this->makeLink());
    }

    public function makeLink(): string
    {
        return sprintf('<a target="_blank" href="%s">Время книг</a>', request()->url());
    }
}
