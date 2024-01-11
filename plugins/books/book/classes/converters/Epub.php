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
        $epub->temp_folder = storage_path().'/app/';
        $epub->epub_file = $this->file->getLocalPath();

        if ($this->has_cover()) {
            $epub->AddImage($this->book->cover->getLocalPath(), true, 1);
        }
        $epub->AddPage($this->annotation(), null, 'Аннотация', true);
        foreach ($this->chapters() as $chapter) {
            $epub->AddPage($chapter->content->body, null, $chapter->title, true);
        }
        $epub->CreateEPUB();
        return 'success';
    }

}
