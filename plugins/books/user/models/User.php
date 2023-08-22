<?php

namespace Books\User\Models;

use Books\Book\Models\Author;
use Books\Profile\Models\Profile;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class User extends \RainLab\User\Models\User
{
    use HasRelationships;

    public function books(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->profiles(), Profile::make()->books());
    }

    public function booksInAuthorOrder(): HasManyDeep
    {
        return $this->books()
            ->withPivot('books_book_authors', ['*'], Author::class)
            ->orderByDesc('books_book_authors.sort_order');
    }

    public static function from(\RainLab\User\Models\User $user)
    {
        return self::find($user->id);
    }
}
