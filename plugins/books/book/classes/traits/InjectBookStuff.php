<?php namespace Books\Book\Classes\Traits;

use Books\Book\Models\Book;

trait InjectBookStuff
{
    public function tryInjectAdultModel()
    {
        if (!$this->validate() || !is_null($this->book)) {
            return false;
        }
        if ($this->book = Book::query()->onlyPublicStatus()->find($this->book_id) ?? abort(404)) {
            $this->page['ask_adult'] = askAboutAdult($this->book);
        }

    }

    public function addMeta()
    {
        if (!$this->validate()) {
            return false;
        }
        $this->page->meta_title = '«' . $this->book?->title . '»';
        $this->page->meta_preview = $this->book?->cover?->path;
        $this->page->meta_description = strip_tags($this->book?->annotation);
    }

    protected function validate()
    {
        return property_exists($this, 'book') && property_exists($this, 'book_id');
    }
}
