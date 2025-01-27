<?php

namespace Books\Book\Classes;

use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use October\Rain\Database\Collection;
use October\Rain\Database\Model;

class StatisticService
{
    protected string $format = 'd.m.y';

    protected CarbonPeriod $period;

    protected string $class = Book::class;

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

    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    public function getPeriod(): CarbonPeriod
    {
        return $this->period;
    }

    public function get(Model|Collection ...$needle)
    {
        $dates = collect($this->period->toArray())->reverse();
        $books = $this->class::query()
            ->whereIn('id', collect($needle)->pluck('id'))
            ->when($this->class === Chapter::class, fn ($q) => $q->public())
            ->with(['trackers' => fn ($trackers) => $trackers->withoutTodayScope()->completed()])
            ->get();

        $books->map(function ($book) {
            $book->tracks = $book->trackers->groupBy(fn ($i) => $i->updated_at->format($this->format));

            return $book;
        });

        $common = $dates->map(function ($date) use ($books) {
            $key = $date->format($this->format);
            $filtered = $books
                ->filter(fn ($i) => $i->tracks->has($key))
                ->each(fn ($book) => $book['count'] = $book->tracks->get($key)->count() ?? 0);

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
                        'count' => $book->tracks->get($key)?->count() ?? 0,
                    ];
                }),
            ];
        });

        $reversed = $common->reverse();
        $graph = [
            'columns' => $reversed->pluck('date')->join(','),
            'rows' => $reversed->pluck('total')->join(','),
        ];

        return [
            'common' => $common,
            'graph' => $graph,
            'byBooks' => $byBooks,
        ];
    }
}
