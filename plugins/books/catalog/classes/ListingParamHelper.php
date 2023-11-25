<?php

namespace Books\Catalog\Classes;

use Books\Catalog\Models\Genre;
use Books\Catalog\Models\Type;

/**
 * Хэлпер для параметров каталога книг. Содержит модель и тип параметра.
 *
 * @property Genre|Type $model
 * @property ListingParamsEnum $type
 */
class ListingParamHelper
{
    public function __construct(public Genre|Type $model, public ListingParamsEnum $type)
    {
    }

    /**
     * Ищет сущность по списку из ListingParamsEnum.
     * Поиск по умолчанию по id, указанному в get параметре.
     * Если передать slug, будет использован slug scope.
     * Жанр имеет приоритет перед типом.
     *
     * @return ?static $helper
     */
    public static function lookUp(string $slag = null): ?static
    {
        foreach (ListingParamsEnum::cases() as $type) {
            if ($item = $slag ? $type->findBySlug($slag) : $type->findFromUrl()) {
                return new static($item, $type);
            }
        }

        return null;
    }
}
