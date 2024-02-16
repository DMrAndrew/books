<?php namespace Books\Book\Classes\Traits;

use Books\Book\Models\Book;
use Books\User\Classes\UserService;
use Str;

trait InjectBookStuff
{
    public function tryInjectAdultModal()
    {

        if (!$this->validate()) {
            return false;
        }

        if ($this->book) {
            $needToShowModalAskAboutAdult = needShowModalAskAboutAdult($this->book);
        } else if ($this->book_id) {
            $book = Book::query()->onlyPublicStatus()->find($this->book_id);
            $needToShowModalAskAboutAdult = needShowModalAskAboutAdult($book);
        }

        $this->page['ask_adult'] = $needToShowModalAskAboutAdult;// && !UserService::allowedSeeAdult();
    }

    public function addMeta()
    {
        if (!$this->validate()) {
            return false;
        }

        /**
         * ХНазваниеХ – ХавторХ скачать в fb2, epub, txt, pdf или читать онлайн бесплатно
         */
        $title = $this->book?->title . ' - ';
        $bookAuthors = $this->book?->orderedAuthors();
        if ($bookAuthors) {
            $title .= implode(', ', $bookAuthors->pluck('profile.username')->toArray());
        }
        $title .= " скачать в fb2, epub, txt, pdf или читать онлайн бесплатно";

        $this->page->meta_title = $title;
        $this->page->meta_preview = $this->book?->cover?->path;
        $this->page->meta_description = strip_tags($this->book?->annotation);
    }

    protected function validate()
    {
        return property_exists($this, 'book') && property_exists($this, 'book_id');
    }
}
