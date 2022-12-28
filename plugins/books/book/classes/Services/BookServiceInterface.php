<?php

namespace Books\Book\Classes\Services;

use Books\Book\Models\Tag;
use Books\Book\Models\Book;
use RainLab\User\Models\User;
use Books\Catalog\Models\Genre;
use Illuminate\Support\Collection;

interface BookServiceInterface
{
    public function save(array $data): Book;

    public function modifyCoAuthorPercent(int $user_id, int $value);

    public function addTag(Tag $tag);

    public function addGenre(Genre $genre);

    public function addCoAuthor(User $coAuthor);

    public function getCoAuthors($pivot = false);

    public function getGenres(): Collection;

    public function getTags(): Collection;

    public function removeGenre(Genre $genre);

    public function removeTag(Tag $tag);

    public function removeCoAuthor(User $author);
}
