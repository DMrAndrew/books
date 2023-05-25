<?php

namespace App\classes;

use Illuminate\Pagination\LengthAwarePaginator;

class CustomPaginator extends LengthAwarePaginator
{
    public function getLinks()
    {
        $links = $this->linkCollection()->map(function ($item) {
            return in_array($item['label'],
                [$this->lastPage(),
                    1,
                    $this->currentPage(),
                    $this->currentPage() + 1,
                    $this->currentPage() - 1,
                ])
                ? $item : null;
        });

        $links = $links->filter(function ($value, $key) use ($links) {
            return $value || ($key !== 0 && ($links[$key + 1] ?? false));
        })->map(function ($value) {
            return $value ? array_merge($value, ['url' => $value['label']]) : ['url' => null, 'label' => '...', 'active' => false];
        });
        if ($this->currentPage() > 1) {
            $links->prepend(['url' => $this->currentPage() - 1, 'label' => 'Назад', 'active' => false]);
        }

        if ($this->hasMorePages()) {
            $links->push(['url' => $this->currentPage() + 1, 'label' => 'Вперёд', 'active' => false]);
        }

        if ($links->count() === 1) {
            $links->pop();
        }

        return $links;
    }

    public static function fromLengthAwarePaginator(LengthAwarePaginator $paginator): static
    {
        return new static($paginator->getCollection(), $paginator->total(), $paginator->perPage(), $paginator->currentPage());
    }
}
