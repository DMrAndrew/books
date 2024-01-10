<?php

namespace Books\Book\Classes\Converters;

use Books\Book\Classes\Enums\ElectronicFormats;
use Books\Book\Classes\Epub\TPEpubCreator;

class Epub extends BaseConverter
{
    public ElectronicFormats $format = ElectronicFormats::EPUB;

    public function generate(): string
    {
        $domain = env('APP_URL');
        $this->file->fromData('', $this->filename());
        $epub = new TPEpubCreator();
        $epub->language = 'ru';
        $epub->uuid = md5($this->book->title);
        $epub->title = $this->title();
        $epub->creator = $domain;
        $epub->publisher = $domain;
        $epub->temp_folder = storage_path();
        $epub->epub_file = $this->file->getLocalPath();

        if ($this->has_cover()) {
            $epub->AddImage($this->book->cover->getLocalPath(), true, true);
        }
        $epub->AddPage($this->annotation(), null, 'Аннотация', true);
        $chapters = $this->chapters()->values();
        $count = count($chapters);
        foreach ($chapters as $index => $chapter) {
            $epub->AddPage($chapter->content->body.($count === ($index - 1) ? $this->endMark() : ''), null, $chapter->title, true);
        }
        $epub->CreateEPUB();
        return 'success';
    }

}
