<?php

namespace Books\Book\Classes\Converters;

use Books\Book\Classes\Enums\ElectronicFormats;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Twig;

class PDF extends BaseConverter
{
    public ElectronicFormats $format = ElectronicFormats::PDF;

    public function generate(): string
    {
        ini_set('pcre.backtrack_limit', '5000000');
        $html = Twig::parse(file_get_contents(plugins_path('/books/book/views/pdf_template.htm')), [
            'title' => $this->book->title,
            'authors' => $this->book->profiles->pluck('username')->join(', '),
            'img_src' => $this->book->cover->getLocalPath(),
            'annotation' => $this->annotation(),
            'chapters' => $this->chapters(),
            'endmark' => $this->endMark(),
        ]);
        $pdf = new Mpdf(['tempDir' => storage_path('/temp/electronic_books_generate')]);
        $pdf->SetTitle($this->title());
        $pdf->useSubstitutions = false;
        $pdf->simpleTables = true;
        $pdf->SetAuthor($this->book->profile->username);
        $pdf->SetKeywords($this->book->tags->pluck('name')->join(', '));
        $pdf->SetCreator(env('APP_URL'));

        $pdf->WriteHTML($html);

        return $pdf->Output(null, Destination::STRING_RETURN);
    }

    public function __destruct()
    {
        ini_set('pcre.backtrack_limit', '1000000');
    }
}
