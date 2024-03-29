<?php

namespace Books\Book\Components;

use Books\Book\Classes\BookService;
use Books\Book\Models\Edition;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Log;
use October\Rain\Database\Collection;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Redirect;
use ValidationException;

/**
 * LCBooker Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class LCBooker extends ComponentBase
{
    protected User $user;

    protected ?bool $is_owner = null;

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'LCBooker Component',
            'description' => 'Компонент книг личного кабинета',
        ];
    }

    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $this->user = Auth::getUser();
        $this->page['authorships'] = $this->getAuthorships();
    }

    public function getAuthorships(): Collection
    {
        return $this->user->profile
            ->authorships()
            ->owner($this->getIsOwner())
            ->with([
                'book' => fn($q) => $q->defaultEager(),
                'book.profile',
                'book.editions' => fn ($q) => $q->withPriceEager(),
            ])
            ->get()
            ->sortByDesc('sort_order');
    }

    public function onChangeOrder()
    {
        //TODO
        $sequence = collect(post('sequence'));
        $authorships = $this->getAuthorships();
        $newsequence = (collect([$sequence, $authorships->pluck('sort_order')])->map->reverse()->map->values()->map->toArray())->toArray();
        $authorships->first()->setSortableOrder(...$newsequence);

        return $this->generateList();
    }

    public function onFilter()
    {
        return $this->generateList();
    }

    public function generateList(?array $options = [])
    {
        return [
            '#books_list_partial' => $this->renderPartial('@list', ['authorships' => $this->getAuthorships(), 'is_owner' => $this->getOwnerFilter(), ...$options]),
        ];
    }

    public function onUploadFile()
    {
        try {
            $uploadedFile = (new Edition())->fb2()->withDeferred($this->getSessionKey())->get()?->first();
            if (!$uploadedFile) {
                throw new ValidationException(['fb2' => 'Файл не найден.']);
            }

            (new BookService(user: $this->user, session_key: $this->getSessionKey()))->from($uploadedFile);

            return Redirect::refresh();
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());
            Log::error($ex->getMessage());
            return [];
        }
    }

    /**
     * defineProperties for the component
     *
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function onRefreshFiles()
    {
        $this->pageCycle();
    }

    public function getSessionKey()
    {
        return post('_session_key');
    }

    public function getOwnerFilter(): bool|string|null
    {
        return match ($this->getIsOwner()) {
            true => 'owner',
            false => 'coauthor',
            default => null,
        };
    }

    public function getIsOwner(): ?bool
    {
        $this->is_owner = match (post('is_owner')) {
            'owner' => true,
            'coauthor' => false,
            default => null,
        };

        return $this->is_owner;
    }
}
