<?php

namespace Books\Book\Classes;

use Books\Book\Models\Book;
use Carbon\CarbonPeriod;

class StatisticService
{
    protected string $format = 'd.m.y';
    protected CarbonPeriod $period;

    public function __construct()
    {
        $from = today()->subDays(7);
        $to = today()->addDays(7);
        $this->period = (new CarbonPeriod($from, '1 day', $to));
    }

    public function getDates(){
        return collect($this->period->toArray())->map->format($this->format);
    }
    public function get(Book ...$books)
    {

        $columns = $this->getDates();
         $book = Book::query()
             ->when(count($books),fn($q) => $q->whereIn('id', collect($books)->pluck('id')))
            ->with('paginationTrackers')
            ->get();
        return $groupedSt = $book->map(function ($book) use ($columns) {
            $read_statistics = $book->paginationTrackers->groupBy(fn($i) => $i->created_at->format($this->format));
            return [
                'title' => $book->title,
                'id' => $book->id,
                'read_statistics' => $columns->map(function ($date) use ($read_statistics) {
                    return [
                        'date' => $date,
                        'count' =>  $read_statistics->has($date) ? $read_statistics->get($date)->count() : 0
                    ];
                })

            ];

        });
    }

}
