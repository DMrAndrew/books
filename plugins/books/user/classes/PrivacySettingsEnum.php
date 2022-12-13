<?php

namespace Books\User\Classes;

enum PrivacySettingsEnum: string
{
    case ALL = 'all';
    case SUBSCRIBERS = 'sub';
    case NONE = 'none';

    public static function values(): array
    {
        return collect(static::cases())->map->value->toArray();
    }

    public static function default(): string
    {
        return self::NONE->value;
    }

}
