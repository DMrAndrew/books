<?php

namespace Books\Book\Classes;

use Exception;
use Queue;
use Books\Book\Models\Book;

class Rater
{
    protected array $closures = [];
    protected array $scopes = [
        'likes' => 'likesCount',
        'libs' => 'inLibCount',
        'comments' => 'commentsCount',
    ];

    public function __construct(protected Book $book)
    {
    }

    public function apply(): void
    {
        if ($this->book->id) {
            $query = Book::query();

            foreach (array_keys($this->closures) as $closure) {
                if ($scope = $this->scopes[$closure] ?? false) {
                    $query->{$scope}();
                }
            }

            $this->book = $query->find($this->book->id);

            foreach ($this->closures as $closure) {
                $closure();
            }
        }
        $this->closures = [];
    }

    public function queue()
    {
        $actions = array_keys($this->closures);
        if (!count($actions)) {
            return null;
        }
        $id = $this->book->id;
        return Queue::push(function ($job) use ($id, $actions) {
            try {
                $r = new static(Book::find($id));
                foreach ($actions as $action) {
                    $r->{$action}();
                }
                $r->apply();
                $job->delete();
                return true;
            } catch (Exception $exception) {
                //
                return false;
            }
        });
    }

    public function rate(): static
    {
        $this->likes();
        $this->closures['rate'] = function () {
            $this->book['rate'] = $this->book['likes_count']; // Пока есть только лайки
            $this->update('rate');
        };
        return $this;
    }

    public function read(): static
    {
        $this->closures['read'] = function () {
            $this->book['read_count'] = $this->book->ebook->chapters()->withReadTrackers()->get()->sum('completed_trackers');
            $this->update('read_count');
        };
        return $this;
    }

    public function likes(): static
    {
        $this->closures['likes'] = fn() => $this->update('likes_count');

        return $this;
    }

    public function libs(): static
    {
        $this->closures['libs'] = fn() => $this->update('in_lib_count');

        return $this;
    }

    public function comments(): static
    {
        $this->closures['comments'] = fn() => $this->update('comments_count');

        return $this;
    }

    public function allStats(): static
    {
        $this->likes();
        $this->comments();
        $this->libs();
        $this->read();
        $this->rate();
        return $this;
    }

    public static function recompute(string ...$stats): void
    {
        if (!count($stats)) {
            $stats = ['allStats'];
        }

        //TODO cursor
        $raters = Book::all()->map->rater();
        foreach ($stats as $stat) {
            if (method_exists(static::class, $stat)) {
                $raters->each->{$stat}();
            }
        }
        $raters->each->queue();
    }

    private function update(string $stat_key, ?string $book_key = null)
    {
        return $this->book->stats->update([$stat_key => $this->book[$book_key ?? $stat_key]]);
    }


}
