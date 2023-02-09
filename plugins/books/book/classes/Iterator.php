<?php

namespace Books\Book\Classes;

class Iterator
{
    public function __construct(protected array $array)
    {
    }

    public function seek(int $value): void
    {
        reset($this->array);
        while (current($this->array) !== $value) {
            next($this->array);
        }

    }

    public function next()
    {
        $next = next($this->array);
        if (!$next) {
            end($this->array);
        }
        return $next;
    }

    public function prev()
    {
        $prev = prev($this->array);
        if (!$prev) {
            reset($this->array);
        }
        return $prev;
    }

    public function current()
    {
        return current($this->array);
    }

    public function hasNext(): ?bool
    {
        return $this->array[key($this->array) + 1] ?? null;
    }

    public function hasPrev(): ?bool
    {
        return $this->array[key($this->array) - 1] ?? null;
    }
}

