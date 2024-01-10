<?php

namespace Books\Book\Classes\Converters;

use Books\Book\Classes\Enums\ElectronicFormats;
use MOBIClass\MOBIFile;

class MOBI extends BaseConverter
{
    public ElectronicFormats $format = ElectronicFormats::MOBI;

    public function generate(): string
    {
        $mobi = new \MOBIClass\MOBI();
        $content = new MOBIFile();
        $content->set('title', $this->book->title);
        $content->set('author', $this->book->profile->username);
        $method = 'imagecreatefrom' . (explode('/', $this->book->cover->getContentType())[1]);
        $cover = $method($this->book->cover->getLocalPath());
        $content->appendImage($cover);
        $content->appendParagraph($this->annotation());
        $content->appendPageBreak();
        foreach ($this->chapters() as $chapter) {
            $content->appendChapterTitle($chapter->title);
            $content->appendParagraph($chapter->content->body);
            $content->appendPageBreak();
        }
        $content->appendParagraph($this->endMark());
        $mobi->setContentProvider($content);

        return $mobi->toString();
    }
}
