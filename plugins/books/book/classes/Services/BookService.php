<?php

namespace Books\Book\Classes\Services;

use Books\Book\Models\Book;
use RainLab\User\Models\User;

abstract class BookService implements BookServiceInterface
{
    public function __construct(protected ?string $session_key, protected Book $book, protected User $user)
    {
    }

    /**
     * @return string|null
     */
    public function getSessionKey(): ?string
    {
        return $this->session_key;
    }
}
