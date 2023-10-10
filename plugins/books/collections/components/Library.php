<?php

namespace Books\Collections\Components;

use Books\Book\Models\Book;
use Books\Collections\classes\CollectionEnum;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * Library Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Library extends ComponentBase
{
    protected ?User $user;

    public function componentDetails()
    {
        return [
            'name' => 'Library Component',
            'description' => 'No description provided yet...',
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        $this->user = Auth::getUser();
        $this->bindCollection();
    }

    public function bindCollection(CollectionEnum $activeTab = null)
    {
        if ($this->user) {
            $lib = $this->user->getLib();
            $lib[CollectionEnum::BOUGHT->value] = $this->user->ownedBooks()->with('ownable')->get()->map->ownable;
            $collection = collect(CollectionEnum::cases())->map(function ($item) use ($lib) {
                return [
                    'label' => $item->label(),
                    'type' => $item,
                    'count' => count($lib[$item->value] ?? []),
                    'items' => $lib[$item->value] ?? [],
                ];
            });

            $this->page['user'] = $this->user;
            $this->page['library'] = $collection;
            $this->page['library_has_items'] = (bool) $collection->pluck('count')->sum();
            $this->page['active'] = $activeTab ?: CollectionEnum::READING;
        }
    }

    public function renderLibrary()
    {
        $this->bindCollection(CollectionEnum::tryFrom(post('active_tab')));

        return [
            '#library' => $this->renderPartial('@default'),
        ];
    }

    public function onRemove()
    {
        $this->user->library(Book::find(post('book_id')))?->remove();

        return $this->renderLibrary();
    }

    public function onSwitch()
    {
        $book = Book::find(post('book_id'));
        $action = post('action');
        if ($book && in_array($action, ['loved', 'remove', 'interested', 'watched', 'reading', 'read', 'unloved'])) {
            $this->user->library($book)->{$action}();
        }

        return $this->renderLibrary();
    }
}
