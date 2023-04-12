<?php

namespace Books\Notifications\Classes;

enum NotificationEnum: int
{
    case System = 1;
    case CoAuthorRequest = 2;
    case CommentReplied = 3;
    case CoAuthorAccepted = 4;
    case BookCompleted = 5;

    public static function accountable(): array
    {
        return [
            self::System->value => self::System,
        ];
    }

    public static function profilable(): array
    {
        return [
            self::CoAuthorRequest->value => self::CoAuthorRequest,
            self::CommentReplied->value => self::CommentReplied,
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

    public function label(): string
    {
        return match ($this) {
            self::System => 'Системное сообщение',
            self::CoAuthorRequest => 'Соавторство',
            self::CommentReplied => 'Ответ на комментарий',
            self::CoAuthorAccepted => 'Соавторство принято',
        };
    }
}
