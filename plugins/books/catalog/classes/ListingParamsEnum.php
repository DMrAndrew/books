<?php

namespace Books\Catalog\Classes;

use Books\Catalog\Models\Genre;
use Books\Catalog\Models\Type;
use October\Rain\Support\Collection;

enum ListingParamsEnum: string
{
    case GENRE = 'genre';
    case TYPE = 'type';

    public function getUrlValue(): ?int
    {
        return is_numeric(get($this->value)) ? (int) get($this->value) : null;
    }

    public function findFromUrl(): Genre|Type|null
    {
        return $this->model()::find($this->getUrlValue());
    }

    public function findBySlug(string $slag): Genre|Type|null
    {
        return $this->model()::query()->slug($slag)->first();
    }

    public function model(): string
    {
        return match ($this) {
            self::GENRE => Genre::class,
            self::TYPE => Type::class,

        };
    }

    public function filterKey(): string
    {
        return match ($this) {
            self::GENRE => 'genreSlug',
            self::TYPE => 'typeSlag',

        };
    }

    public static function toCollection(): Collection|\Illuminate\Support\Collection
    {
        return collect(ListingParamsEnum::cases());
    }

    public static function toArray(): array
    {
        return self::toCollection()->toArray();
    }

    public static function values(): Collection|\Illuminate\Support\Collection
    {
        return self::toCollection()->pluck('value');
    }
}
