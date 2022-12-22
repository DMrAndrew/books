<?php

namespace Books\Book\Classes;

use Books\Book\Models\Book;
use Books\Book\Models\BookStatus;
use Books\Book\Models\Chapter;
use Carbon\Carbon;
use Db;
use Event;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use System\Models\File;
use Tizis\FB2\FB2Controller;

class FB2Manager
{
    public FB2Controller $parsed;
    protected string $session_key;

    public function __construct()
    {
        $this->session_key = uuid_create();
    }


    public function apply($fb2)
    {
        return Db::transaction(function () use ($fb2) {

            $file = file_get_contents($fb2);
            $this->parsed = new FB2Controller($file);
            $this->parsed->withNotes();
            $this->parsed->startParse();


            $info = $this->parsed->getBook()->getInfo();
            $user = Auth::getUser() ?? User::query()->first();

            $book = new Book([
                'title' => $info->getTitle(),
                'annotation' => strip_tags($info->getAnnotation()),
                'user_id' => $user->id,
                'price' => 0,
                'status' => BookStatus::HIDDEN
            ]);

            $cover = (new File())->fromData(base64_decode($this->parsed->getBook()->getCover()), 'cover.jpg');
            $cover->save();
            $book->cover()->add($cover, $this->session_key);

            $at_least_one_author_in_the_app_is_found = false;
            foreach ($this->parsed->getBook()->getAuthors() as $author) {
                //TODO улучшить поиск по пользователям
                if ($coauthor = User::username($author->getFullName())->first()) {
                    if ($coauthor->id === $user->id) {
                        continue;
                    }
                    if (!$at_least_one_author_in_the_app_is_found) {
                        $book->coauthors()->add($user, $this->session_key, ['percent' => 100]);
                    }
                    $book->coauthors()->add($coauthor, $this->session_key, ['percent' => 0]);
                    $at_least_one_author_in_the_app_is_found = true;
                }
            }

            $chapters = collect($this->parsed->getBook()->getChapters())->map(fn($chapter, $key) => new Chapter([
                'title' => $chapter->getTitle(),
                'content' => $chapter->getContent(),
                'published_at' => Carbon::now(),
                'sort_order' => $key + 1
            ]));
            $book->save(null, $this->session_key);

            foreach ($chapters as $chapter) {
                $book->chapters()->add($chapter);
            }
            $words = $info->getKeywords();
            collect(explode(',', $words))->each(function ($i) use ($book, $user) {
                $tag = $user->tags()->firstOrCreate(['name' => mb_ucfirst($i)]);
                $book->tags()->add($tag);
            });
            Event::fire('books.book.created', $book);
            return $book;
        });
    }

}
