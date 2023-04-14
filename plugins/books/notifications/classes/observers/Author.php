<?php

namespace Books\Notifications\Classes\Observers;

class Author
{
    public function created(\Books\Book\Models\Author $author)
    {
        dd($author);
    }
}
