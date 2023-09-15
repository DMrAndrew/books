<?php

namespace App\telegram;

use NotificationChannels\Telegram\TelegramMessage;

enum TChatsEnum: int
{
    case PERSONAL = 1;

    public function id(): string
    {
        return match ($this) {
            self::PERSONAL => config('services.telegram-bot-api.chat_id'),

        };
    }

    public function make(): TelegramMessage
    {
        return TelegramMessage::create()->to($this->id());
    }
}
