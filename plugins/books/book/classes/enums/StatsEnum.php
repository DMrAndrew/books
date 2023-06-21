<?php

namespace Books\Book\Classes\Enums;

use Exception;

enum StatsEnum: string
{
    case LIKES = 'likes';
    case LIBS = 'libs';
    case COMMENTS = 'comments';
    case READ = 'read'; //прочтение остальных глав
    case READ_INITIAL = 'read_initial'; //показатель перехода с первой на вторую главу
    case READ_FINAL = 'read_final'; //показатель прочтения последней главы
    case RATE = 'rate';

    case READ_TIME = 'time';

    case UPDATE_FREQUENCY = 'frequency';

    case COLLECTED_GENRE_RATE = 'collected_genre_rate';
    case collected_gain_popularity_rate = 'collected_gain_popularity_rate';
    case collected_hot_new_rate = 'collected_hot_new_rate';
    case sells_count = 'sells_count';

    public function mapStatAttribute(): string
    {
        return match ($this) {
            self::LIKES => 'likes_count',
            self::LIBS => 'in_lib_count',
            self::COMMENTS => 'comments_count',
            self::READ => 'read_count',
            self::READ_INITIAL => 'read_initial_count',
            self::READ_FINAL => 'read_final_count',
            self::UPDATE_FREQUENCY => 'freq',
            default => $this->value
        };
    }
}
