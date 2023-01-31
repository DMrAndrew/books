<?php

namespace Books\Book\Classes;


use Books\Book\Classes\Exceptions\FBParserException;
use Books\Book\Models\Book;
use Books\Book\Models\ChapterStatus;
use Books\Profile\Models\Profile;
use Db;
use Event;
use League\Csv\Exception;
use RainLab\User\Models\User;
use System\Models\File;
use Tizis\FB2\FB2Controller;
use Tizis\FB2\Model\BookInfo;

class FB2Manager
{
    protected BookInfo $info;

    protected FB2Controller $parser;

    public function __construct(protected User            $user,
                                protected ?string         $session_key = null,
                                protected ?Book           $book = null,
                                protected ?BookService    $bookService = null,
                                protected ?ChapterManager $chapterManager = null,
    )
    {
        $this->session_key ??= uuid_create();
        $this->book ??= new Book();
        $this->bookService ??= new BookService(user: $this->user, book: $this->book, session_key: $this->session_key);
    }


    public function apply(File $fb2)
    {
        return Db::transaction(function () use ($fb2) {


            $file = file_get_contents($fb2->getLocalPath());

            try {
                $this->parser = new FB2Controller($file);
                $this->parser->withNotes();
                $this->parser->startParse();
            }
            catch (\Exception $exception){
                throw new FBParserException();
            }


            $this->info = $this->parser->getBook()->getInfo();

            $data = [
                'title' => strip_tags($this->info->getTitle()),
                'annotation' => $this->info->getAnnotation(),
            ];

            $cover = (new File())->fromData(base64_decode($this->parser->getBook()->getCover()), 'cover.jpg');
            $cover->save();
            $this->book->cover()->add($cover, $this->session_key);

//            foreach ($this->parser->getBook()->getAuthors() as $author) {
//                //TODO улучшить поиск по пользователям
//                if ($profile = Profile::username($author->getFullName())->first()) {
//                    $this->bookService->addProfile($profile);
//                }
//            }

            $keywords = collect(explode(',', $this->info->getKeywords()));
            $keywords->each(function ($i) {
                $this->bookService->addTag($i);
            });

            $this->book = $this->bookService->save($data);

            if(!$this->book->ebook){
                throw new \Exception('Электронное издание книги не найдено. Обратитесь к администратору.');
            }

            $this->chapterManager = new ChapterManager($this->book->ebook, false);

            collect($this->parser->getBook()->getChapters())
                ->map(fn($chapter, $key) => $this->chapterManager->create([
                    'title' => $chapter->getTitle(),
                    'content' => $chapter->getContent(),
                    'sort_order' => $key + 1,
                    'status' => ChapterStatus::PUBLISHED,
                ]));

            Event::fire('books.book.parsed', [$this->book]);
            return $this->book;

        });
    }
}
