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
        $method = 'imagecreatefrom'.(explode('/', $this->book->cover->getContentType())[1]);
        $cover = $method($this->book->cover->getLocalPath());
        $content->appendImage($cover);
        $content->appendParagraph(
            '<i>'.$this->book->annotation.'</i>'
        );
        $content->appendParagraph(
            '<strong>'.$this->book->profiles()->pluck('username')->join(', ').'</strong>'
        );
        $content->appendParagraph($this->printDate()->format('d.m.Y'));
        $url = env('APP_URL', '');
        $content->appendParagraph(
            sprintf('<i>Данный текст был загружен на портале <a href="%s" target="_blank">Время книг</a>.</i>', $url)
        );
        $content->appendParagraph('***');
        $content->appendPageBreak();
        foreach ($this->chapters() as $chapter) {
            $content->appendChapterTitle($chapter->title);
            $content->appendParagraph($chapter->content->body);
            $content->appendPageBreak();
        }
        $mobi->setContentProvider($content);

        return $mobi->toString();
    }
}
