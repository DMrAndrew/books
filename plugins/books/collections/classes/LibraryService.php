<?php

namespace Books\Collections\classes;

use Books\Book\Models\Book;
use Books\Collections\Models\Lib;
use Illuminate\Support\Traits\Conditionable;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\HasMany;
use RainLab\User\Models\User;

class LibraryService
{
    use Conditionable;

    public function __construct(protected User $user, protected Book $book)
    {
    }

    public function build(): HasMany
    {
        return $this->user->libs()
            ->whereHasMorph('favorable', [Lib::class], fn (Builder $builder) => $builder->book($this->book));
    }

    public function watched(): bool
    {
        return $this->get()->update(['type' => CollectionEnum::WATCHED]);
    }

    public function interested(): bool
    {
        return $this->get()->update(['type' => CollectionEnum::INTERESTED]);
    }

    public function reading(): bool
    {
        return $this->get()->update(['type' => CollectionEnum::READING]);
    }

    public function read(): bool
    {
        return $this->get()->update(['type' => CollectionEnum::READ]);
    }

    public function loved(): bool
    {
        return $this->get()->update(['loved' => 1]);
    }

    public function unloved()
    {
        return $this->get()->update(['loved' => 0]);
    }

    //TODO ??

    public function remove()
    {
        return $this->has() && $this->get()->delete();
    }

    public function is(CollectionEnum $type)
    {
        return $this->has() && $this->get()->type === $type;
    }

    public function has(): bool
    {
        return $this->build()->exists();
    }

    public function get(): Lib
    {
        return ($this->build()?->first() ?? $this->add())->favorable;
    }

    public function add()
    {
        return $this->user->addFavorite(
            Lib::query()->create(['book_id' => $this->book->id])
        );
    }
}
