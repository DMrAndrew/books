<?php

namespace Books\Book\Classes;

use Books\Book\Models\Book;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class StatisticService
{
    protected string $format = 'd.m.y';

    protected CarbonPeriod $period;

    public function __construct(protected Carbon $from, protected ?Carbon $to = null)
    {
        if (! $this->to) {
            $this->to = $this->from;
        }
        $this->period = (new CarbonPeriod($from, '1 day', $to));
        if ($this->period->count() > 62) {
            $this->format = 'm.y';
            $this->period = (new CarbonPeriod($from, '1 month', $this->to));
            if ($this->period->count() > 12) {
                $this->period = (new CarbonPeriod($from, '1 month', $this->from->copy()->addYear()));
            }
        }
    }

    /**
     * @return CarbonPeriod
     */
    public function getPeriod(): CarbonPeriod
    {
        return $this->period;
    }

    public function get(Book ...$needle)
    {
        $dates = collect($this->period->toArray());
        $books = Book::query()
            ->when(count($needle), fn ($q) => $q->whereIn('id', collect($needle)->pluck('id')))
            ->with('paginationTrackers')
            ->get();

        $books->each(fn ($book) => $book->trackers = $book->paginationTrackers->groupBy(fn ($i) => $i->created_at->format($this->format)));

        $common = $dates->map(function ($date) use ($books) {
            $key = $date->format($this->format);
            $filtered = $books
                ->filter(fn ($i) => $i->trackers->has($key))
                ->each(fn ($book) => $book['count'] = $book->trackers->get($key)->count() ?? 0);

            return [
                'date' => $this->format === 'd.m.y' ? $date->format('d.m') : $key,
                'total' => $filtered->reduce(fn ($acc, $book) => $acc + $book['count'], 0),
                'items' => $filtered->map->only(['id', 'title', 'count']),
            ];
        });

        $byBooks = $books->map(function ($book) use ($dates) {
            return [
                'title' => $book->title,
                'dates' => $dates->map(function ($date) use ($book) {
                    $key = $date->format($this->format);

                    return [
                        'date' => $date->format($this->format),
                        'count' => $book->trackers->get($key)?->count() ?? 0,
                    ];
                }),
            ];
        });

        $graph = [
            'columns' => $common->pluck('date')->join(','),
            'rows' => $common->pluck('total')->join(','),
        ];

        return [
            'common' => $common,
            'graph' => $graph,
            'byBooks' => $byBooks,
        ];

        $books->map(function ($book) use ($dates) {
            return [
                'title' => $book->title,
                'dates' => $dates->map(function ($date) {
                    return ['date' => $date->format()];
                }),
            ];
        });
    }
}
