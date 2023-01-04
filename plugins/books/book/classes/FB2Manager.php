<?php

namespace Books\Book\Classes;

use Db;
use Event;
use System\Models\File;
use Books\Book\Models\Book;
use Tizis\FB2\FB2Controller;
use Tizis\FB2\Model\BookInfo;
use RainLab\User\Models\User;
use Books\Book\Models\Chapter;
use Books\Book\Classes\Services\CreateBookService;

class FB2Manager
{
    protected FB2Controller $parsed;

    protected BookInfo $info;
    protected CreateBookService $bookService;

    public function __construct(protected ?string $session_key, protected User $user, protected ?Book $book = null)
    {
        $this->session_key ??= uuid_create();
        $this->book ??= new Book();
        $this->bookService = new CreateBookService($this->session_key, $this->book, $this->user);
    }


    public function apply(File $fb2)
    {
        return Db::transaction(function () use ($fb2) {

            $file = file_get_contents($fb2->getLocalPath());
            $this->parsed = new FB2Controller($file);
            $this->parsed->withNotes();
            $this->parsed->startParse();

            $this->info = $this->parsed->getBook()->getInfo();

            $data = [
                'title' => strip_tags($this->info->getTitle()),
                'annotation' => $this->info->getAnnotation(),
            ];

            $cover = (new File())->fromData(base64_decode($this->parsed->getBook()->getCover()), 'cover.jpg');
            $cover->save();
            $this->book->cover()->add($cover, $this->session_key);

            foreach ($this->parsed->getBook()->getAuthors() as $author) {
                //TODO улучшить поиск по пользователям
                if ($coauthor = User::username($author->getFullName())->first()) {
                    if ($coauthor->id === $this->user->id) {
                        continue;
                    }
                    $this->bookService->addCoAuthor($coauthor);
                }
            }

            $keywords = collect(explode(',', $this->info->getKeywords()));
            $keywords->filter(fn($i) => !!$i)->each(function ($i) {
                $tag = $this->user->tags()->firstOrCreate(['name' => mb_ucfirst($i)]);
                $this->bookService->addTag($tag);
            });

            $this->book = $this->bookService->save($data);

            $chapters = collect($this->parsed->getBook()->getChapters())->map(fn($chapter, $key) => new Chapter([
                'title' => plainText($chapter->getTitle()),
                'content' => $chapter->getContent(),
                'sort_order' => $key + 1
            ]));

            foreach ($chapters as $chapter) {
                $this->book->chapters()->add($chapter);
            }

            Event::fire('books.book.chapters.parsed', $this->book);

            return $this->book;

        });
    }
}
