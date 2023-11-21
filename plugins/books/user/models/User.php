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
            ->withPivot(Author::make()->getTable(), ['*'], Author::class)
            ->orderBy(Author::make()->qualifyColumn('sort_order'),'desc');
    }

    public static function from(\RainLab\User\Models\User $user)
    {
        return self::find($user->id);
    }
}
