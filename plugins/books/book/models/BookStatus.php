<?php

namespace Books\Book\Models;

enum BookStatus:string
{
    case IN_WORK = 'in_work';
    case COMPLETE = 'complete';
    case FROZEN = 'frozen';

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return match($this){
            self::IN_WORK => 'В работе',
            self::COMPLETE => 'Завершена',
            self::FROZEN => 'Заморожена',
        };
    }
}
