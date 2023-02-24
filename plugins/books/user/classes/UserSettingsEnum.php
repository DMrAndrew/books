<?php

namespace Books\User\Classes;

enum UserSettingsEnum: int
{
    case NOTIFY_UPDATE_LIBRARY_ITEMS = 1;
    case NOTIFY_BOOK_DISCOUNT = 2;
    case NOTIFY_NEW_RECORD_BLOG = 3;
    case NOTIFY_NEW_RECORD_VIDEO_BLOG = 4;
    case NOTIFY_UPDATE_STORE_ITEMS = 5;
    case PRIVACY_ALLOW_FIT_ACCOUNT_INDEX_PAGE = 6;
    case PRIVACY_ALLOW_PRIVATE_MESSAGING = 7;
    case PRIVACY_ALLOW_VIEW_COMMENT_FEED = 8;
    case PRIVACY_ALLOW_VIEW_BLOG = 9;
    case PRIVACY_ALLOW_VIEW_VIDEO_BLOG = 10;

    public function label(): string
    {
        return match ($this) {
            self::NOTIFY_UPDATE_LIBRARY_ITEMS => 'Обновление книг в моей библиотеке',
            self::NOTIFY_BOOK_DISCOUNT => 'Скидки на книги',
            self::NOTIFY_NEW_RECORD_BLOG => 'Новые записи в блоге',
            self::NOTIFY_NEW_RECORD_VIDEO_BLOG => 'Новые записи в видеоблоге',
            self::NOTIFY_UPDATE_STORE_ITEMS => 'Обновление товаров в магазине',
            self::PRIVACY_ALLOW_FIT_ACCOUNT_INDEX_PAGE => 'Кто может писать на моей странице',
            self::PRIVACY_ALLOW_PRIVATE_MESSAGING => 'Кто может писать мне личные сообщения',
            self::PRIVACY_ALLOW_VIEW_COMMENT_FEED => 'Кто может видеть ленту моих комментариев',
            self::PRIVACY_ALLOW_VIEW_BLOG => 'Кто может видеть мой блог',
            self::PRIVACY_ALLOW_VIEW_VIDEO_BLOG => 'Кто может видеть мой видеоблог',
        };
    }

    public function defaultValue(): bool|string
    {
        return match ($this) {
            self::NOTIFY_UPDATE_LIBRARY_ITEMS, self::NOTIFY_BOOK_DISCOUNT, self::NOTIFY_NEW_RECORD_BLOG, self::NOTIFY_NEW_RECORD_VIDEO_BLOG, self::NOTIFY_UPDATE_STORE_ITEMS => BoolOptionsEnum::default(),
            self::PRIVACY_ALLOW_FIT_ACCOUNT_INDEX_PAGE, self::PRIVACY_ALLOW_PRIVATE_MESSAGING, self::PRIVACY_ALLOW_VIEW_COMMENT_FEED, self::PRIVACY_ALLOW_VIEW_BLOG, self::PRIVACY_ALLOW_VIEW_VIDEO_BLOG => PrivacySettingsEnum::default(),
        };
    }

    public function options()
    {
        return match ($this) {
            self::NOTIFY_UPDATE_LIBRARY_ITEMS, self::NOTIFY_BOOK_DISCOUNT, self::NOTIFY_NEW_RECORD_BLOG, self::NOTIFY_NEW_RECORD_VIDEO_BLOG, self::NOTIFY_UPDATE_STORE_ITEMS => BoolOptionsEnum::cases(),
            self::PRIVACY_ALLOW_FIT_ACCOUNT_INDEX_PAGE, self::PRIVACY_ALLOW_PRIVATE_MESSAGING, self::PRIVACY_ALLOW_VIEW_COMMENT_FEED, self::PRIVACY_ALLOW_VIEW_BLOG, self::PRIVACY_ALLOW_VIEW_VIDEO_BLOG => PrivacySettingsEnum::cases(),
        };
    }

    public static function privacy(): array
    {
        return [
            self::PRIVACY_ALLOW_FIT_ACCOUNT_INDEX_PAGE,
            self::PRIVACY_ALLOW_PRIVATE_MESSAGING,
            self::PRIVACY_ALLOW_VIEW_BLOG,
            self::PRIVACY_ALLOW_VIEW_COMMENT_FEED,
            self::PRIVACY_ALLOW_VIEW_VIDEO_BLOG
        ];
    }

    public static function notify(): array
    {
        return [
            self::NOTIFY_BOOK_DISCOUNT,
            self::NOTIFY_NEW_RECORD_BLOG,
            self::NOTIFY_UPDATE_STORE_ITEMS,
            self::NOTIFY_NEW_RECORD_VIDEO_BLOG,
            self::NOTIFY_UPDATE_LIBRARY_ITEMS,
        ];
    }

    public static function profilable(): array
    {
        return [
            self::NOTIFY_NEW_RECORD_BLOG,
            self::NOTIFY_NEW_RECORD_VIDEO_BLOG,
            self::NOTIFY_UPDATE_STORE_ITEMS,
            self::PRIVACY_ALLOW_FIT_ACCOUNT_INDEX_PAGE,
            self::PRIVACY_ALLOW_PRIVATE_MESSAGING,
            self::PRIVACY_ALLOW_VIEW_COMMENT_FEED,
            self::PRIVACY_ALLOW_VIEW_BLOG,
            self::PRIVACY_ALLOW_VIEW_VIDEO_BLOG,
        ];
    }

    public static function accountable(): array
    {
        return [
            self::NOTIFY_UPDATE_LIBRARY_ITEMS,
            self::NOTIFY_BOOK_DISCOUNT,
        ];
    }

    public function isAccountable(): bool
    {
        return in_array($this, self::accountable());
    }

    public function isProfilable(): bool
    {
        return in_array($this, self::profilable());
    }

}
