<?php

namespace App\classes;

enum PartialSpawns: string
{
    case SPAWN_MODAL = '#modal-spawn';
    case SPAWN_EDIT_EBOOK_CHAPTERS = '#ebooker-chapters';
    case SPAWN_EDIT_EBOOK_SETTINGS = '#ebook-settings';
    case SPAWN_EDIT_AUDIOBOOK_CHAPTERS = '#audiobooker-chapters';
    case SPAWN_EDIT_AUDIOBOOK_SETTINGS = '#audiobooker-settings';
    case SPAWN_LC_BLACKLIST = '#lc-blacklist';
}
