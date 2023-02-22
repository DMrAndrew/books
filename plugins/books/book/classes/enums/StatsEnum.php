<?php

namespace Books\Book\Classes\Enums;

enum StatsEnum:string
{
    case LIKES = 'likes';
    case LIBS = 'libs';
    case COMMENTS = 'comments';
    case READ = 'read';
    case RATE = 'rate';
}
