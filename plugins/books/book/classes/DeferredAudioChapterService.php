<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\ChapterStatus;
use Books\Book\Models\Chapter;
use Books\Book\Models\Content;
use Books\Book\Models\Edition;

class DeferredAudioChapterService
{
    protected Chapter $chapter;
    protected ?Edition $edition;

    public function __construct(Chapter $chapter)
    {
        $this->chapter = $chapter;
    }

    public function deferredChapterCreate()
    {
        $content = Content::create([
            'contentable' => $this->chapter,
        ]);
    }

    public function saveDeferredChapter(ChapterStatus $status)
    {
        if ($status) {

        }
        $content = Content::create([
            'contentable' => $this->chapter,
        ]);
    }
}
