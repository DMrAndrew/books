<?php namespace Books\Book\Classes\Traits;

use Books\Book\Models\Book;

trait InjectAdultAgreementModel
{
    public function tryInjectAdultModel()
    {
        if (!property_exists($this, 'book') || !property_exists($this, 'book_id') || !is_null($this->book)) {
            return false;
        }
        if ($this->book = Book::query()->onlyPublicStatus()->find($this->book_id) ?? abort(404)) {
            $this->page['ask_adult'] = askAboutAdult($this->book);
        }

    }
}
