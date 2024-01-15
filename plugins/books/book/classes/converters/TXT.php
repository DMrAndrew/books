<?php

namespace Books\Book\Classes\Converters;

use Books\Book\Classes\Enums\ElectronicFormats;
use Soundasleep\Html2Text;

class TXT extends BaseConverter
{
    public ElectronicFormats $format = ElectronicFormats::TXT;

    public function generate(): string
    {
        $content = sprintf('<p>%s</p>', $this->book->title);
        $content .= $this->annotation();
        foreach ($this->chapters() as $chapter) {
            $content .= $chapter->title;
            $content .= $chapter->content->body;
        }

        return Html2Text::convert($content, true);
    }
}
