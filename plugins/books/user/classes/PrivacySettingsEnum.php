<?php

namespace Books\User\Classes;

enum PrivacySettingsEnum: string
{
    case ALL = 'all';
    case SUBSCRIBERS = 'sub';
    case NONE = 'none';

    public function label(): string
    {
        return match ($this) {
            self::ALL => 'Все',
            self::SUBSCRIBERS => 'Только подписчики',
            self::NONE => 'Никто',
        };
    }

    public static function values(): array
    {
        return collect(static::cases())->map->value->toArray();
    }

    public static function default(): PrivacySettingsEnum
    {
        return self::ALL;
    }
}
