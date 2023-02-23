<?php

namespace Books\Book\Classes\Enums;

enum StatsEnum: string
{
    case LIKES = 'likes';
    case LIBS = 'libs';
    case COMMENTS = 'comments';
    case READ = 'read';
    case READ_INITIAL = 'read_initial'; //показатель перехода с первой на вторую главу
    case READ_FINAL = 'read_final'; //показатель прочтения последней главы
    case RATE = 'rate';

    case READ_TIME = 'time';

    case UPDATE_FREQUENCY = 'frequency';
}
