<?php

namespace Books\Collections\behaviors;

use Books\Book\Models\Book;
use Books\Collections\classes\CollectionEnum;
use Books\Collections\classes\LibraryService;
use Books\Collections\Models\Lib;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;

class HasLibrary extends ExtensionBase
{
    public function __construct(protected User $model)
    {
    }

    public function library(Book $model): LibraryService
    {
        return new LibraryService($this->model, $model);
    }

    public function queryLibs()
    {
        return $this->model
            ->favorites()
            ->type(Lib::class);
    }

    public function libs(): HasMany
    {
        return $this->model->queryLibs()
            ->with(['favorable' => fn ($q) => $q->with(['book' => fn ($book) => $book->defaultEager()])]);
    }

    public function getLib(bool $withPercent = true)
    {
        $libs = $this->model->libs()
            ->whereHas('favorable', fn ($favorable) => $favorable->public())
            ->with(['favorable' => fn ($q) => $q->with(['book.ebook' => fn ($ebook) => $ebook->withReadLength($this->model)])])
            ->get()
            ->pluck('favorable')
            ->sortByDesc('id')
            ->groupBy(fn ($i) => $i->type->value);

        if ($libs[CollectionEnum::READING->value] ?? false) {
            $libs[CollectionEnum::READING->value] = $libs[CollectionEnum::READING->value]?->sortByDesc(fn ($c) => $c->book->ebook->read_percent);
        }
        $libs[CollectionEnum::LOVED->value] = $libs->flatten(1)->filter(fn ($i) => $i->loved);

        return $libs;
    }
}
