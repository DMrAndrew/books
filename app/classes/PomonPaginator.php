<?php

namespace App\classes;

use Illuminate\Pagination\LengthAwarePaginator;

class PomonPaginator extends LengthAwarePaginator
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

        return $links->filter(function ($value, $key) use ($links) {
            return  $value || ($key !== 0 && ($links[$key + 1]  ?? false));
        })->map(function ($value) {
            return $value ?: ['url' => null, 'label' => '...', 'active' => false];
        });
    }
}
