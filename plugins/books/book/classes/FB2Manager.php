<?php

namespace Books\Book\Classes;

use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Books\Book\Models\CoAuthor;
use Db;
use RainLab\User\Models\User;
use System\Models\File;
use Tizis\FB2\FB2Controller;

class FB2Manager
{
    public $item = null;

    public function __construct()
    {
        $file = file_get_contents(__DIR__ . '/book.fb2');
        $this->item = new FB2Controller($file);
        $this->item->withNotes();
        $this->item->startParse();




        Db::transaction(function (){
            $info = $this->item->getBook()->getInfo();
            $user = User::query()->first();

            $book = Book::create([
                'title' => $info->getTitle(),
                'annotation' => $info->getAnnotation(),
                'author_id' => $user->id
            ]);
            $book->cover()->add((new File())->fromData(base64_decode($this->item->getBook()->getCover()), 'cover.jpg'));

            foreach ($this->item->getBook()->getAuthors() as $author) {
                if ($exist = User::username($author->getFullName())->first())
                    $book->coauthors()->add(new CoAuthor(['author_id' => $exist->id]));
            }

            $chapters = collect($this->item->getBook()->getChapters())->map(fn($chapter) => new Chapter([
                'title' => $chapter->getTitle(),
                'content' => $chapter->getContent(),
            ]));
            $book->chapters()->saveMany($chapters);
        });

    }

}
